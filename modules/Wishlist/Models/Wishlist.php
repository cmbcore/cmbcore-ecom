<?php

declare(strict_types=1);

namespace Modules\Wishlist\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Product\Models\Product;

class Wishlist extends Model
{
    protected $fillable = [
        'user_id',
        'product_id',
    ];

    /**
     * @return BelongsTo<User, Wishlist>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Product, Wishlist>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
