<?php

declare(strict_types=1);

namespace Modules\Review\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Product\Models\Product;

class ProductReview extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'product_id',
        'user_id',
        'rating',
        'title',
        'content',
        'status',
        'is_verified_purchase',
        'admin_reply',
    ];

    protected $casts = [
        'is_verified_purchase' => 'boolean',
    ];

    /**
     * @return BelongsTo<Product, ProductReview>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * @return BelongsTo<User, ProductReview>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<ReviewImage>
     */
    public function images(): HasMany
    {
        return $this->hasMany(ReviewImage::class, 'review_id')->orderBy('id');
    }
}
