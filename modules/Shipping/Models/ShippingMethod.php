<?php

declare(strict_types=1);

namespace Modules\Shipping\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShippingMethod extends Model
{
    public const TYPE_FLAT_RATE = 'flat_rate';
    public const TYPE_FREE = 'free';
    public const TYPE_CALCULATED = 'calculated';

    protected $fillable = [
        'shipping_zone_id',
        'name',
        'code',
        'type',
        'fee',
        'free_shipping_threshold',
        'min_order_value',
        'max_order_value',
        'conditions',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'fee' => 'decimal:2',
        'free_shipping_threshold' => 'decimal:2',
        'min_order_value' => 'decimal:2',
        'max_order_value' => 'decimal:2',
        'conditions' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * @return BelongsTo<ShippingZone, ShippingMethod>
     */
    public function zone(): BelongsTo
    {
        return $this->belongsTo(ShippingZone::class, 'shipping_zone_id');
    }
}
