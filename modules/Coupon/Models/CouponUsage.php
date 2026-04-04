<?php

declare(strict_types=1);

namespace Modules\Coupon\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CouponUsage extends Model
{
    protected $fillable = [
        'coupon_id',
        'order_id',
        'user_id',
        'guest_email',
        'code',
        'discount_total',
        'used_at',
    ];

    protected $casts = [
        'discount_total' => 'decimal:2',
        'used_at' => 'datetime',
    ];

    /**
     * @return BelongsTo<Coupon, CouponUsage>
     */
    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }
}
