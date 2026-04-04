<?php

declare(strict_types=1);

namespace Modules\Cart\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Product\Models\Product;
use Modules\Product\Models\ProductSku;

class ShoppingCartItem extends Model
{
    protected $fillable = [
        'cart_id',
        'product_id',
        'product_sku_id',
        'quantity',
        'product_name',
        'sku_name',
        'unit_price',
        'compare_price',
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'compare_price' => 'decimal:2',
    ];

    /**
     * @return BelongsTo<ShoppingCart, ShoppingCartItem>
     */
    public function cart(): BelongsTo
    {
        return $this->belongsTo(ShoppingCart::class, 'cart_id');
    }

    /**
     * @return BelongsTo<Product, ShoppingCartItem>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * @return BelongsTo<ProductSku, ShoppingCartItem>
     */
    public function sku(): BelongsTo
    {
        return $this->belongsTo(ProductSku::class, 'product_sku_id');
    }
}
