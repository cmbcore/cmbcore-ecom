<?php

declare(strict_types=1);

namespace Modules\Address\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Province extends Model
{
    protected $table = 'address_provinces';

    protected $fillable = [
        'code',
        'name',
        'english_name',
        'administrative_level',
        'decree',
    ];

    /** @return HasMany<Commune> */
    public function communes(): HasMany
    {
        return $this->hasMany(Commune::class, 'province_code', 'code');
    }
}
