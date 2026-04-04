<?php

declare(strict_types=1);

namespace Modules\FlashSale\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FlashSale extends Model
{
    protected $fillable = [
        'title',
        'starts_at',
        'ends_at',
        'is_active',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    /**
     * @return HasMany<FlashSaleItem>
     */
    public function items(): HasMany
    {
        return $this->hasMany(FlashSaleItem::class)->orderBy('id');
    }
}
