<?php

declare(strict_types=1);

namespace Modules\Cart\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ShoppingCart extends Model
{
    public const STATUS_ACTIVE = 'active';
    public const STATUS_CONVERTED = 'converted';
    public const STATUS_ABANDONED = 'abandoned';
    public const STATUS_MERGED = 'merged';

    protected $fillable = [
        'user_id',
        'guest_token',
        'status',
    ];

    /**
     * @return BelongsTo<User, ShoppingCart>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<ShoppingCartItem>
     */
    public function items(): HasMany
    {
        return $this->hasMany(ShoppingCartItem::class, 'cart_id')->orderBy('id');
    }
}
