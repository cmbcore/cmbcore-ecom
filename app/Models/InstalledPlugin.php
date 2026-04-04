<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InstalledPlugin extends Model
{
    protected $fillable = [
        'name',
        'alias',
        'version',
        'is_active',
        'settings',
        'installed_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'settings' => 'array',
        'installed_at' => 'datetime',
    ];
}
