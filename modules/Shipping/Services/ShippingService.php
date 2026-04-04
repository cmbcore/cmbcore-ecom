<?php

declare(strict_types=1);

namespace Modules\Shipping\Services;

use Illuminate\Support\Str;
use Modules\Shipping\Models\ShippingMethod;
use Modules\Shipping\Models\ShippingZone;

class ShippingService
{
    /**
     * @return array<string, mixed>
     */
    public function adminPayload(): array
    {
        return [
            'zones' => ShippingZone::query()
                ->with('methods')
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get()
                ->map(fn (ShippingZone $zone): array => [
                    'id' => $zone->id,
                    'name' => $zone->name,
                    'provinces' => $zone->provinces ?? [],
                    'is_active' => (bool) $zone->is_active,
                    'sort_order' => (int) $zone->sort_order,
                    'methods' => $zone->methods->map(fn (ShippingMethod $method): array => $this->methodPayload($method))->all(),
                ])
                ->all(),
            'methods' => ShippingMethod::query()
                ->with('zone')
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get()
                ->map(fn (ShippingMethod $method): array => $this->methodPayload($method))
                ->all(),
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function saveZone(array $payload): array
    {
        $zone = ShippingZone::query()->updateOrCreate(
            ['id' => isset($payload['id']) ? (int) $payload['id'] : null],
            [
                'name' => trim((string) ($payload['name'] ?? '')),
                'provinces' => $this->normalizeList($payload['provinces'] ?? []),
                'is_active' => (bool) ($payload['is_active'] ?? true),
                'sort_order' => (int) ($payload['sort_order'] ?? 0),
            ],
        );

        return [
            'id' => $zone->id,
            'name' => $zone->name,
            'provinces' => $zone->provinces ?? [],
            'is_active' => (bool) $zone->is_active,
            'sort_order' => (int) $zone->sort_order,
        ];
    }

    public function deleteZone(int $id): void
    {
        ShippingZone::query()->findOrFail($id)->delete();
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function saveMethod(array $payload): array
    {
        $method = ShippingMethod::query()->updateOrCreate(
            ['id' => isset($payload['id']) ? (int) $payload['id'] : null],
            [
                'shipping_zone_id' => ! empty($payload['shipping_zone_id']) ? (int) $payload['shipping_zone_id'] : null,
                'name' => trim((string) ($payload['name'] ?? '')),
                'code' => $this->resolveCode($payload),
                'type' => $this->normalizeType((string) ($payload['type'] ?? ShippingMethod::TYPE_FLAT_RATE)),
                'fee' => (float) ($payload['fee'] ?? 0),
                'free_shipping_threshold' => $this->nullableFloat($payload['free_shipping_threshold'] ?? null),
                'min_order_value' => $this->nullableFloat($payload['min_order_value'] ?? null),
                'max_order_value' => $this->nullableFloat($payload['max_order_value'] ?? null),
                'conditions' => is_array($payload['conditions'] ?? null) ? $payload['conditions'] : [],
                'is_active' => (bool) ($payload['is_active'] ?? true),
                'sort_order' => (int) ($payload['sort_order'] ?? 0),
            ],
        );

        $method->load('zone');

        return $this->methodPayload($method);
    }

    public function deleteMethod(int $id): void
    {
        ShippingMethod::query()->findOrFail($id)->delete();
    }

    /**
     * @param  array<string, mixed>  $cartPayload
     * @param  array<string, mixed>  $shippingAddress
     * @return array<string, mixed>
     */
    public function quote(array $cartPayload, array $shippingAddress, ?int $selectedMethodId = null): array
    {
        $province = trim((string) ($shippingAddress['province'] ?? ''));
        $subtotal = (float) ($cartPayload['subtotal'] ?? 0);
        $weight = (float) collect((array) ($cartPayload['items'] ?? []))
            ->sum(fn (array $item): float => ((float) ($item['unit_weight'] ?? 0)) * ((int) ($item['quantity'] ?? 0)));

        $methods = ShippingMethod::query()
            ->with('zone')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->filter(fn (ShippingMethod $method): bool => $this->methodMatches($method, $province, $subtotal))
            ->map(function (ShippingMethod $method) use ($subtotal, $weight): array {
                $fee = $this->resolveFee($method, $subtotal, $weight);

                return [
                    'id' => $method->id,
                    'name' => $method->name,
                    'code' => $method->code,
                    'type' => $method->type,
                    'fee' => $fee,
                    'zone' => $method->zone?->name,
                ];
            })
            ->values()
            ->all();

        $selectedMethod = collect($methods)->firstWhere('id', $selectedMethodId) ?? ($methods[0] ?? [
            'id' => null,
            'name' => 'Mặc định',
            'code' => config('shipping.default_method_code', 'standard'),
            'type' => ShippingMethod::TYPE_FLAT_RATE,
            'fee' => 0,
            'zone' => null,
        ]);

        return [
            'methods' => $methods,
            'selected_method' => $selectedMethod,
            'shipping_total' => (float) ($selectedMethod['fee'] ?? 0),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function methodPayload(ShippingMethod $method): array
    {
        return [
            'id' => $method->id,
            'shipping_zone_id' => $method->shipping_zone_id,
            'name' => $method->name,
            'code' => $method->code,
            'type' => $method->type,
            'fee' => (float) $method->fee,
            'free_shipping_threshold' => $method->free_shipping_threshold !== null ? (float) $method->free_shipping_threshold : null,
            'min_order_value' => $method->min_order_value !== null ? (float) $method->min_order_value : null,
            'max_order_value' => $method->max_order_value !== null ? (float) $method->max_order_value : null,
            'conditions' => $method->conditions ?? [],
            'is_active' => (bool) $method->is_active,
            'sort_order' => (int) $method->sort_order,
            'zone' => $method->zone ? [
                'id' => $method->zone->id,
                'name' => $method->zone->name,
            ] : null,
        ];
    }

    private function methodMatches(ShippingMethod $method, string $province, float $subtotal): bool
    {
        if ($method->zone && $method->zone->is_active === false) {
            return false;
        }

        if ($method->min_order_value !== null && $subtotal < (float) $method->min_order_value) {
            return false;
        }

        if ($method->max_order_value !== null && $subtotal > (float) $method->max_order_value) {
            return false;
        }

        if (! $method->zone || $province === '') {
            return true;
        }

        $provinces = collect($method->zone->provinces ?? [])
            ->map(fn (mixed $item): string => Str::lower(trim((string) $item)))
            ->filter()
            ->all();

        if ($provinces === []) {
            return true;
        }

        return in_array(Str::lower($province), $provinces, true);
    }

    private function resolveFee(ShippingMethod $method, float $subtotal, float $weight): float
    {
        if ($method->type === ShippingMethod::TYPE_FREE) {
            return 0.0;
        }

        if ($method->free_shipping_threshold !== null && $subtotal >= (float) $method->free_shipping_threshold) {
            return 0.0;
        }

        if ($method->type === ShippingMethod::TYPE_CALCULATED) {
            $perKg = (float) data_get($method->conditions ?? [], 'per_kg', 0);

            return round((float) $method->fee + ($perKg * ceil(max(0, $weight))), 2);
        }

        return round((float) $method->fee, 2);
    }

    /**
     * @return array<int, string>
     */
    private function normalizeList(mixed $value): array
    {
        if (is_string($value)) {
            $value = preg_split('/[\r\n,]+/', $value) ?: [];
        }

        if (! is_array($value)) {
            return [];
        }

        return array_values(array_filter(array_map(
            static fn (mixed $item): string => trim((string) $item),
            $value,
        )));
    }

    private function normalizeType(string $type): string
    {
        return in_array($type, [
            ShippingMethod::TYPE_FLAT_RATE,
            ShippingMethod::TYPE_FREE,
            ShippingMethod::TYPE_CALCULATED,
        ], true)
            ? $type
            : ShippingMethod::TYPE_FLAT_RATE;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function resolveCode(array $payload): string
    {
        $code = trim((string) ($payload['code'] ?? ''));

        if ($code !== '') {
            return Str::slug($code, '_');
        }

        return Str::slug((string) ($payload['name'] ?? 'shipping'), '_');
    }

    private function nullableFloat(mixed $value): ?float
    {
        return is_numeric($value) ? (float) $value : null;
    }
}
