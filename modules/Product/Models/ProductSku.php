<?php

declare(strict_types=1);

namespace Modules\Product\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductSku extends Model
{
    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';

    protected $fillable = [
        'product_id',
        'sku_code',
        'name',
        'price',
        'compare_price',
        'cost',
        'weight',
        'stock_quantity',
        'low_stock_threshold',
        'barcode',
        'status',
        'sort_order',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'compare_price' => 'decimal:2',
        'cost' => 'decimal:2',
        'weight' => 'decimal:2',
    ];

    /**
     * @return HasMany<ProductSkuAttribute>
     */
    public function attributes(): HasMany
    {
        return $this->hasMany(ProductSkuAttribute::class)->orderBy('id');
    }

    /**
     * @return HasMany<ProductMedia>
     */
    public function media(): HasMany
    {
        return $this->hasMany(ProductMedia::class)->orderBy('position')->orderBy('id');
    }

    /**
     * @return BelongsTo<Product, ProductSku>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * @param  Builder<ProductSku>  $query
     * @return Builder<ProductSku>
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }
}
