<?php

declare(strict_types=1);

namespace Modules\Blog\Services;

use App\Services\StorefrontDataReadiness;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator as Paginator;
use Modules\Blog\Models\BlogCategory;
use Modules\Blog\Models\BlogPost;

class BlogCatalogService
{
    public function __construct(
        private readonly StorefrontDataReadiness $readiness,
    ) {
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, BlogPost>
     */
    public function paginate(array $filters = []): LengthAwarePaginator
    {
        if (! $this->readiness->hasBlog()) {
            return $this->emptyPaginator();
        }

        $perPage = max(1, (int) theme_setting('blog_posts_per_page', config('blog.storefront_per_page', 9)));
        $selectedCategory = $this->selectedCategory($filters);

        return BlogPost::query()
            ->published()
            ->with('category')
            ->when(
                $selectedCategory instanceof BlogCategory,
                static fn (Builder $query) => $query->where('blog_category_id', $selectedCategory->id),
            )
            ->when(
                filled($filters['search'] ?? null),
                function (Builder $query) use ($filters): void {
                    $search = '%' . trim((string) $filters['search']) . '%';
                    $query->where(function (Builder $searchQuery) use ($search): void {
                        $searchQuery
                            ->where('title', 'like', $search)
                            ->orWhere('slug', 'like', $search)
                            ->orWhere('excerpt', 'like', $search)
                            ->orWhere('author_name', 'like', $search);
                    });
                },
            )
            ->ordered()
            ->paginate($perPage)
            ->withQueryString();
    }

    public function findBySlug(string $slug): BlogPost
    {
        abort_unless($this->readiness->hasBlog(), 404);

        /** @var BlogPost $post */
        $post = BlogPost::query()
            ->published()
            ->with('category')
            ->where('slug', $slug)
            ->firstOrFail();

        $post->increment('view_count');

        return $post;
    }

    /**
     * @return Collection<int, BlogPost>
     */
    public function relatedPosts(BlogPost $post, int $limit = 3): Collection
    {
        if (! $this->readiness->hasBlog()) {
            return new Collection();
        }

        return BlogPost::query()
            ->published()
            ->with('category')
            ->whereKeyNot($post->id)
            ->when(
                $post->blog_category_id !== null,
                static fn (Builder $query) => $query->where('blog_category_id', $post->blog_category_id),
            )
            ->ordered()
            ->limit($limit)
            ->get();
    }

    /**
     * @return Collection<int, BlogCategory>
     */
    public function categories(): Collection
    {
        if (! $this->readiness->hasBlog()) {
            return new Collection();
        }

        return BlogCategory::query()
            ->active()
            ->ordered()
            ->get();
    }

    public function selectedCategory(array $filters = []): ?BlogCategory
    {
        $slug = trim((string) ($filters['category'] ?? ''));

        if ($slug === '') {
            return null;
        }

        return $this->findCategoryBySlug($slug);
    }

    public function findCategoryBySlug(string $slug): ?BlogCategory
    {
        if (! $this->readiness->hasBlog()) {
            return null;
        }

        return BlogCategory::query()
            ->active()
            ->where('slug', trim($slug))
            ->first();
    }

    /**
     * @return Collection<int, BlogPost>
     */
    public function recentPosts(int $limit = 4): Collection
    {
        if (! $this->readiness->hasBlog()) {
            return new Collection();
        }

        return BlogPost::query()
            ->published()
            ->with('category')
            ->ordered()
            ->limit($limit)
            ->get();
    }

    /**
     * @return array<int, array{label:string, url:string}>
     */
    public function listingBreadcrumbs(?BlogCategory $category = null): array
    {
        $breadcrumbs = [
            [
                'label' => theme_text('navigation.home'),
                'url' => theme_home_url(),
            ],
            [
                'label' => theme_text('navigation.blog'),
                'url' => theme_route_url('storefront.blog.index'),
            ],
        ];

        if (! $category instanceof BlogCategory) {
            return $breadcrumbs;
        }

        $breadcrumbs[] = [
            'label' => $category->name,
            'url' => theme_route_url('storefront.blog.categories.show', ['slug' => $category->slug]),
        ];

        return $breadcrumbs;
    }

    /**
     * @return array<int, array{label:string, url:string}>
     */
    public function postBreadcrumbs(BlogPost $post): array
    {
        return array_merge($this->listingBreadcrumbs($post->category), [[
            'label' => $post->title,
            'url' => theme_url($post->slug),
        ]]);
    }

    /**
     * @return LengthAwarePaginator<int, BlogPost>
     */
    private function emptyPaginator(): LengthAwarePaginator
    {
        $perPage = max(1, (int) theme_setting('blog_posts_per_page', config('blog.storefront_per_page', 9)));
        $currentPage = max(1, (int) request()->integer('page', 1));

        return new Paginator(
            [],
            0,
            $perPage,
            $currentPage,
            [
                'path' => Paginator::resolveCurrentPath(),
                'pageName' => 'page',
            ],
        );
    }
}
