<?php

declare(strict_types=1);

namespace Modules\FlashSale\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Product\Models\Product;
use Modules\Product\Models\ProductSku;

class FlashSaleItem extends Model
{
    protected $fillable = [
        'flash_sale_id',
        'product_id',
        'product_sku_id',
        'sale_price',
        'quantity_limit',
        'sold_quantity',
    ];

    protected $casts = [
        'sale_price' => 'decimal:2',
    ];

    /**
     * @return BelongsTo<FlashSale, FlashSaleItem>
     */
    public function flashSale(): BelongsTo
    {
        return $this->belongsTo(FlashSale::class);
    }

    /**
     * @return BelongsTo<Product, FlashSaleItem>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * @return BelongsTo<ProductSku, FlashSaleItem>
     */
    public function sku(): BelongsTo
    {
        return $this->belongsTo(ProductSku::class, 'product_sku_id');
    }
}
