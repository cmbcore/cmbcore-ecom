<?php

declare(strict_types=1);

namespace Modules\Coupon\Services;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Modules\Coupon\Models\Coupon;
use Modules\Coupon\Models\CouponUsage;
use Modules\Order\Models\Order;

class CouponService
{
    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, Coupon>
     */
    public function paginate(array $filters = []): LengthAwarePaginator
    {
        $search = trim((string) ($filters['search'] ?? ''));

        return Coupon::query()
            ->when($search !== '', function (Builder $query) use ($search): void {
                $like = '%' . $search . '%';
                $query->where('code', 'like', $like)
                    ->orWhere('description', 'like', $like);
            })
            ->latest('id')
            ->paginate((int) ($filters['per_page'] ?? config('coupon.per_page', 20)));
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function save(array $payload): Coupon
    {
        /** @var Coupon $coupon */
        $coupon = Coupon::query()->updateOrCreate(
            ['id' => isset($payload['id']) ? (int) $payload['id'] : null],
            [
                'code' => strtoupper(trim((string) $payload['code'])),
                'type' => (string) $payload['type'],
                'value' => (float) $payload['value'],
                'min_order' => $this->nullableFloat($payload['min_order'] ?? null),
                'max_discount' => $this->nullableFloat($payload['max_discount'] ?? null),
                'usage_limit' => $this->nullableInt($payload['usage_limit'] ?? null),
                'per_user_limit' => $this->nullableInt($payload['per_user_limit'] ?? null),
                'start_date' => $this->nullableDate($payload['start_date'] ?? null),
                'end_date' => $this->nullableDate($payload['end_date'] ?? null),
                'is_active' => (bool) ($payload['is_active'] ?? true),
                'description' => $payload['description'] ?? null,
            ],
        );

        return $coupon->refresh();
    }

    public function delete(int $id): void
    {
        Coupon::query()->findOrFail($id)->delete();
    }

    /**
     * @return array<string, mixed>
     */
    public function quote(
        ?string $code,
        float $subtotal,
        ?User $user = null,
        ?string $guestEmail = null,
    ): array {
        $normalizedCode = strtoupper(trim((string) $code));

        if ($normalizedCode === '') {
            return [
                'applied' => false,
                'code' => null,
                'discount_total' => 0.0,
                'coupon' => null,
            ];
        }

        /** @var Coupon|null $coupon */
        $coupon = Coupon::query()
            ->where('code', $normalizedCode)
            ->first();

        if (! $coupon instanceof Coupon) {
            abort(422, 'Coupon khong ton tai.');
        }

        $this->assertCouponIsAvailable($coupon, $subtotal, $user, $guestEmail);

        $discount = $coupon->type === Coupon::TYPE_FIXED
            ? (float) $coupon->value
            : ($subtotal * ((float) $coupon->value / 100));

        if ($coupon->max_discount !== null) {
            $discount = min($discount, (float) $coupon->max_discount);
        }

        $discount = min($discount, $subtotal);

        return [
            'applied' => true,
            'code' => $coupon->code,
            'discount_total' => round($discount, 2),
            'coupon' => [
                'id' => $coupon->id,
                'code' => $coupon->code,
                'type' => $coupon->type,
                'value' => (float) $coupon->value,
            ],
        ];
    }

    public function consume(
        string $code,
        Order $order,
        float $discountTotal,
        ?User $user = null,
        ?string $guestEmail = null,
    ): void {
        if (trim($code) === '' || $discountTotal <= 0) {
            return;
        }

        /** @var Coupon|null $coupon */
        $coupon = Coupon::query()->where('code', strtoupper(trim($code)))->first();

        if (! $coupon instanceof Coupon) {
            return;
        }

        CouponUsage::query()->create([
            'coupon_id' => $coupon->id,
            'order_id' => $order->id,
            'user_id' => $user?->id,
            'guest_email' => $guestEmail,
            'code' => $coupon->code,
            'discount_total' => $discountTotal,
            'used_at' => now(),
        ]);
    }

    private function assertCouponIsAvailable(Coupon $coupon, float $subtotal, ?User $user = null, ?string $guestEmail = null): void
    {
        abort_unless($coupon->is_active, 422, 'Coupon hien dang tam dung.');
        abort_if($coupon->start_date && Carbon::now()->lt($coupon->start_date), 422, 'Coupon chua den thoi gian su dung.');
        abort_if($coupon->end_date && Carbon::now()->gt($coupon->end_date), 422, 'Coupon da het han.');
        abort_if($coupon->min_order !== null && $subtotal < (float) $coupon->min_order, 422, 'Đơn hàng chua dat gia tri toi thieu de ap dung coupon.');

        if ($coupon->usage_limit !== null) {
            $usageCount = CouponUsage::query()->where('coupon_id', $coupon->id)->count();
            abort_if($usageCount >= $coupon->usage_limit, 422, 'Coupon da het luot su dung.');
        }

        if ($coupon->per_user_limit !== null) {
            $usageQuery = CouponUsage::query()->where('coupon_id', $coupon->id);

            if ($user) {
                $usageQuery->where('user_id', $user->id);
            } elseif ($guestEmail) {
                $usageQuery->where('guest_email', $guestEmail);
            } else {
                return;
            }

            abort_if($usageQuery->count() >= $coupon->per_user_limit, 422, 'Ban da dung het luot cho coupon nay.');
        }
    }

    private function nullableFloat(mixed $value): ?float
    {
        return is_numeric($value) ? (float) $value : null;
    }

    private function nullableInt(mixed $value): ?int
    {
        return is_numeric($value) ? (int) $value : null;
    }

    private function nullableDate(mixed $value): ?Carbon
    {
        return is_string($value) && trim($value) !== '' ? Carbon::parse($value) : null;
    }
}
