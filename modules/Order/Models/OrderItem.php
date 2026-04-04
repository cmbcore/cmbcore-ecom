<?php

declare(strict_types=1);

namespace Modules\Order\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    protected $fillable = [
        'order_id',
        'product_id',
        'product_sku_id',
        'product_name',
        'sku_name',
        'attributes_snapshot',
        'quantity',
        'unit_price',
        'line_total',
        'product_snapshot',
    ];

    protected $casts = [
        'attributes_snapshot' => 'array',
        'product_snapshot' => 'array',
        'unit_price' => 'decimal:2',
        'line_total' => 'decimal:2',
    ];

    /**
     * @return BelongsTo<Order, OrderItem>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
