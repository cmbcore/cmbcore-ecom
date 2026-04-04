<?php

declare(strict_types=1);

namespace Modules\Page\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Page extends Model
{
    use SoftDeletes;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_PUBLISHED = 'published';
    public const STATUS_ARCHIVED = 'archived';

    protected $table = 'pages';

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'title',
        'slug',
        'template',
        'featured_image',
        'excerpt',
        'content',
        'puck_data',
        'status',
        'published_at',
        'view_count',
        'meta_title',
        'meta_description',
        'meta_keywords',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'published_at' => 'datetime',
        'view_count' => 'integer',
        'puck_data' => 'array',
    ];

    public function scopePublished(Builder $query): void
    {
        $query
            ->where('status', self::STATUS_PUBLISHED)
            ->where(function (Builder $publicationQuery): void {
                $publicationQuery
                    ->whereNull('published_at')
                    ->orWhere('published_at', '<=', now());
            });
    }

    public function scopeOrdered(Builder $query): void
    {
        $query
            ->orderByDesc('published_at')
            ->orderByDesc('created_at');
    }
}
