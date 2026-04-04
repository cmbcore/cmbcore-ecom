<?php

declare(strict_types=1);

namespace Modules\Blog\Services;

use App\Core\Plugin\HookManager;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use App\Support\SearchEscape;
use Illuminate\Support\Str;
use Modules\Blog\Models\BlogCategory;
use Modules\Blog\Models\BlogPost;

class BlogService
{
    public function __construct(
        private readonly HookManager $hookManager,
    ) {
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, BlogPost>
     */
    public function paginate(array $filters = []): LengthAwarePaginator
    {
        $perPage = (int) ($filters['per_page'] ?? config('blog.per_page', 15));

        return BlogPost::query()
            ->with('category')
            ->when(
                filled($filters['search'] ?? null),
                function (Builder $query) use ($filters): void {
                    $search = SearchEscape::like((string) $filters['search']);
                    $query->where(function (Builder $searchQuery) use ($search): void {
                        $searchQuery
                            ->where('title', 'like', $search)
                            ->orWhere('slug', 'like', $search)
                            ->orWhere('excerpt', 'like', $search)
                            ->orWhere('author_name', 'like', $search);
                    });
                },
            )
            ->when(
                filled($filters['status'] ?? null),
                static fn (Builder $query) => $query->where('status', (string) $filters['status']),
            )
            ->when(
                array_key_exists('featured', $filters) && $filters['featured'] !== null && $filters['featured'] !== '',
                static fn (Builder $query) => $query->where('is_featured', filter_var($filters['featured'], FILTER_VALIDATE_BOOL)),
            )
            ->when(
                filled($filters['blog_category_id'] ?? null),
                static fn (Builder $query) => $query->where('blog_category_id', (int) $filters['blog_category_id']),
            )
            ->ordered()
            ->paginate($perPage);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): BlogPost
    {
        $data = $this->hookManager->applyFilter('blog.creating', $data);

        return DB::transaction(function () use ($data): BlogPost {
            $post = BlogPost::query()->create($this->payload($data));
            $post = $post->fresh('category');

            $this->hookManager->fire('blog.created', $post);

            return $post;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(BlogPost $post, array $data): BlogPost
    {
        $data = $this->hookManager->applyFilter('blog.updating', $data, $post);

        return DB::transaction(function () use ($post, $data): BlogPost {
            $post->fill($this->payload($data, $post));
            $post->save();

            $post = $post->fresh('category');
            $this->hookManager->fire('blog.updated', $post);

            return $post;
        });
    }

    public function delete(BlogPost $post): void
    {
        DB::transaction(function () use ($post): void {
            $postId = $post->id;
            $post->delete();

            $this->hookManager->fire('blog.deleted', $postId);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function payload(array $data, ?BlogPost $post = null): array
    {
        $title = trim((string) ($data['title'] ?? $post?->title ?? ''));
        $baseSlug = trim((string) ($data['slug'] ?? '')) ?: $title;
        $status = (string) ($data['status'] ?? $post?->status ?? BlogPost::STATUS_DRAFT);
        $publishedAt = $this->resolvePublishedAt($data, $status, $post);

        return [
            'title' => $title,
            'slug' => $this->generateUniqueSlug($baseSlug, $post?->id),
            'blog_category_id' => $this->resolveCategoryId($data['blog_category_id'] ?? $post?->blog_category_id ?? null),
            'author_name' => $this->normalizeNullableString($data['author_name'] ?? $post?->author_name ?? null),
            'featured_image' => $this->normalizeNullableString($data['featured_image'] ?? $post?->featured_image ?? null),
            'excerpt' => $this->normalizeNullableString($data['excerpt'] ?? $post?->excerpt ?? null),
            'content' => $this->normalizeNullableString($data['content'] ?? $post?->content ?? null),
            'status' => $status,
            'published_at' => $publishedAt,
            'is_featured' => (bool) ($data['is_featured'] ?? $post?->is_featured ?? false),
            'meta_title' => $this->normalizeNullableString($data['meta_title'] ?? $post?->meta_title ?? null),
            'meta_description' => $this->normalizeNullableString($data['meta_description'] ?? $post?->meta_description ?? null),
            'meta_keywords' => $this->normalizeNullableString($data['meta_keywords'] ?? $post?->meta_keywords ?? null),
        ];
    }

    private function resolvePublishedAt(array $data, string $status, ?BlogPost $post = null): ?Carbon
    {
        if (array_key_exists('published_at', $data)) {
            $value = $data['published_at'];

            if ($value === null || $value === '') {
                return $status === BlogPost::STATUS_PUBLISHED
                    ? ($post?->published_at ? Carbon::parse($post->published_at) : now())
                    : null;
            }

            return Carbon::parse((string) $value);
        }

        if ($status === BlogPost::STATUS_PUBLISHED) {
            return $post?->published_at ? Carbon::parse($post->published_at) : now();
        }

        return $post?->published_at ? Carbon::parse($post->published_at) : null;
    }

    private function generateUniqueSlug(string $value, ?int $ignoreId = null): string
    {
        $baseSlug = Str::slug($value) ?: 'blog-post';
        $slug = $baseSlug;
        $counter = 2;

        while (
            BlogPost::query()
                ->withTrashed()
                ->when($ignoreId, static fn (Builder $query) => $query->where('id', '!=', $ignoreId))
                ->where('slug', $slug)
                ->exists()
        ) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    private function normalizeNullableString(mixed $value): ?string
    {
        if (! is_scalar($value)) {
            return null;
        }

        $normalized = trim((string) $value);

        return $normalized !== '' ? $normalized : null;
    }

    private function resolveCategoryId(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        $categoryId = (int) $value;

        return BlogCategory::query()->whereKey($categoryId)->exists()
            ? $categoryId
            : null;
    }
}
