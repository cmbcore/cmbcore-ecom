<?php

declare(strict_types=1);

namespace Modules\MediaLibrary\Models;

use Illuminate\Database\Eloquent\Model;

class MediaLibraryItem extends Model
{
    protected $fillable = [
        'filename',
        'disk',
        'path',
        'mime_type',
        'size',
        'width',
        'height',
        'alt_text',
        'folder',
    ];
}
