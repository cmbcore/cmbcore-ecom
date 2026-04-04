<?php

declare(strict_types=1);

namespace Modules\Inventory\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Order\Models\Order;
use Modules\Product\Models\Product;
use Modules\Product\Models\ProductSku;

class StockMovement extends Model
{
    public const TYPE_SALE = 'sale';
    public const TYPE_RESTOCK = 'restock';
    public const TYPE_ADJUSTMENT = 'adjustment';
    public const TYPE_RETURN = 'return';

    protected $fillable = [
        'product_id',
        'sku_id',
        'order_id',
        'type',
        'quantity',
        'reference',
        'note',
    ];

    /**
     * @return BelongsTo<ProductSku, StockMovement>
     */
    public function sku(): BelongsTo
    {
        return $this->belongsTo(ProductSku::class, 'sku_id');
    }

    /**
     * @return BelongsTo<Product, StockMovement>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * @return BelongsTo<Order, StockMovement>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
