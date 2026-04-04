<?php

declare(strict_types=1);

namespace Modules\Tax\Models;

use Illuminate\Database\Eloquent\Model;

class TaxRate extends Model
{
    protected $fillable = [
        'name',
        'province',
        'rate',
        'threshold',
        'is_active',
    ];

    protected $casts = [
        'rate' => 'decimal:4',
        'threshold' => 'decimal:2',
        'is_active' => 'boolean',
    ];
}
