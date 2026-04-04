<?php

declare(strict_types=1);

namespace App\Services;

use Modules\Blog\Models\BlogPost;
use Modules\Page\Models\Page;

class StorefrontSlugResolver
{
    public function __construct(
        private readonly StorefrontDataReadiness $readiness,
    ) {
    }

    /**
     * @return array{type:'page'|'blog',slug:string}|null
     */
    public function resolve(string $path): ?array
    {
        $slug = trim($path, '/');

        if ($slug === '' || str_contains($slug, '/')) {
            return null;
        }

        if ($this->readiness->hasPages() && Page::query()->published()->where('slug', $slug)->exists()) {
            return [
                'type' => 'page',
                'slug' => $slug,
            ];
        }

        if ($this->readiness->hasBlog() && BlogPost::query()->published()->where('slug', $slug)->exists()) {
            return [
                'type' => 'blog',
                'slug' => $slug,
            ];
        }

        return null;
    }
}
