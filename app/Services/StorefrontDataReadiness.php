<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Schema;

class StorefrontDataReadiness
{
    /** @var array<string, bool> */
    private array $cache = [];

    public function hasProducts(): bool
    {
        return $this->hasTables([
            'products',
            'product_skus',
            'product_media',
            'categories',
        ]);
    }

    public function hasProductCategories(): bool
    {
        return $this->hasTables([
            'categories',
        ]);
    }

    public function hasBlog(): bool
    {
        return $this->hasTables([
            'blog_posts',
            'blog_categories',
        ]);
    }

    public function hasPages(): bool
    {
        return $this->hasTables([
            'pages',
        ]);
    }

    /**
     * @param  array<int, string>  $tables
     */
    public function hasTables(array $tables): bool
    {
        sort($tables);
        $key = implode('|', $tables);

        if (! array_key_exists($key, $this->cache)) {
            $this->cache[$key] = collect($tables)
                ->every(static fn (string $table): bool => Schema::hasTable($table));
        }

        return $this->cache[$key];
    }
}
