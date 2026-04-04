<?php

declare(strict_types=1);

namespace Modules\Shipping\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ShippingZone extends Model
{
    protected $fillable = [
        'name',
        'provinces',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'provinces' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * @return HasMany<ShippingMethod>
     */
    public function methods(): HasMany
    {
        return $this->hasMany(ShippingMethod::class)->orderBy('sort_order')->orderBy('id');
    }
}
