<?php

declare(strict_types=1);

namespace Modules\Tax\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Modules\Tax\Models\TaxRate;

class TaxService
{
    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, TaxRate>
     */
    public function paginate(array $filters = []): LengthAwarePaginator
    {
        $search = trim((string) ($filters['search'] ?? ''));

        return TaxRate::query()
            ->when($search !== '', function (Builder $query) use ($search): void {
                $like = '%' . $search . '%';
                $query->where('name', 'like', $like)
                    ->orWhere('province', 'like', $like);
            })
            ->latest('id')
            ->paginate((int) ($filters['per_page'] ?? config('tax.per_page', 20)));
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function save(array $payload): TaxRate
    {
        /** @var TaxRate $rate */
        $rate = TaxRate::query()->updateOrCreate(
            ['id' => isset($payload['id']) ? (int) $payload['id'] : null],
            [
                'name' => trim((string) ($payload['name'] ?? '')),
                'province' => $this->nullableString($payload['province'] ?? null),
                'rate' => (float) ($payload['rate'] ?? 0),
                'threshold' => is_numeric($payload['threshold'] ?? null) ? (float) $payload['threshold'] : null,
                'is_active' => (bool) ($payload['is_active'] ?? true),
            ],
        );

        return $rate->refresh();
    }

    public function delete(int $id): void
    {
        TaxRate::query()->findOrFail($id)->delete();
    }

    /**
     * @param  array<string, mixed>  $shippingAddress
     * @return array<string, mixed>
     */
    public function quote(array $shippingAddress, float $taxableAmount): array
    {
        $province = Str::lower(trim((string) ($shippingAddress['province'] ?? '')));

        /** @var TaxRate|null $rate */
        $rate = TaxRate::query()
            ->where('is_active', true)
            ->orderByRaw('case when province is null then 1 else 0 end')
            ->orderByDesc('id')
            ->get()
            ->first(function (TaxRate $taxRate) use ($province, $taxableAmount): bool {
                if ($taxRate->threshold !== null && $taxableAmount < (float) $taxRate->threshold) {
                    return false;
                }

                if ($taxRate->province === null || trim($taxRate->province) === '') {
                    return true;
                }

                return Str::lower(trim((string) $taxRate->province)) === $province;
            });

        if (! $rate instanceof TaxRate) {
            return [
                'tax_total' => 0.0,
                'rate' => null,
                'name' => null,
            ];
        }

        $tax = round($taxableAmount * ((float) $rate->rate / 100), 2);

        return [
            'tax_total' => $tax,
            'rate' => (float) $rate->rate,
            'name' => $rate->name,
        ];
    }

    private function nullableString(mixed $value): ?string
    {
        return is_scalar($value) && trim((string) $value) !== '' ? trim((string) $value) : null;
    }
}
