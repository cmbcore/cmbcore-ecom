<?php

declare(strict_types=1);

namespace Modules\Product\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Category\Models\Category;
use Modules\Wishlist\Models\Wishlist;

class Product extends Model
{
    use SoftDeletes;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_ARCHIVED = 'archived';

    public const TYPE_SIMPLE = 'simple';
    public const TYPE_VARIABLE = 'variable';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'short_description',
        'status',
        'type',
        'category_id',
        'brand',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'view_count',
        'rating_value',
        'review_count',
        'sold_count',
        'is_featured',
    ];

    protected $casts = [
        'is_featured' => 'boolean',
        'rating_value' => 'decimal:2',
        'review_count' => 'integer',
        'sold_count' => 'integer',
    ];

    /**
     * @return BelongsTo<Category, Product>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    /**
     * @return HasMany<ProductMedia>
     */
    public function media(): HasMany
    {
        return $this->hasMany(ProductMedia::class)->orderBy('position')->orderBy('id');
    }

    /**
     * @return HasMany<ProductSku>
     */
    public function skus(): HasMany
    {
        return $this->hasMany(ProductSku::class)->orderBy('sort_order')->orderBy('id');
    }

    /**
     * @return HasMany<Wishlist>
     */
    public function wishlists(): HasMany
    {
        return $this->hasMany(Wishlist::class)->orderByDesc('id');
    }

    /**
     * @param  Builder<Product>  $query
     * @return Builder<Product>
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->latest('id');
    }

    /**
     * @param  Builder<Product>  $query
     * @return Builder<Product>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }
}
