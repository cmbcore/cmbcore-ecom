<?php

declare(strict_types=1);

namespace Modules\Address\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Commune extends Model
{
    protected $table = 'address_communes';

    protected $fillable = [
        'code',
        'name',
        'english_name',
        'administrative_level',
        'province_code',
        'decree',
    ];

    /** @return BelongsTo<Province, Commune> */
    public function province(): BelongsTo
    {
        return $this->belongsTo(Province::class, 'province_code', 'code');
    }
}
