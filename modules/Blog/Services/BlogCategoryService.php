<?php

declare(strict_types=1);

namespace Modules\Blog\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Blog\Models\BlogCategory;

class BlogCategoryService
{
    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, BlogCategory>
     */
    public function paginate(array $filters = []): LengthAwarePaginator
    {
        $perPage = (int) ($filters['per_page'] ?? 15);

        return BlogCategory::query()
            ->when(
                filled($filters['search'] ?? null),
                function (Builder $query) use ($filters): void {
                    $search = '%' . trim((string) $filters['search']) . '%';
                    $query->where(function (Builder $searchQuery) use ($search): void {
                        $searchQuery
                            ->where('name', 'like', $search)
                            ->orWhere('slug', 'like', $search);
                    });
                },
            )
            ->when(
                filled($filters['status'] ?? null),
                static fn (Builder $query) => $query->where('status', (string) $filters['status']),
            )
            ->ordered()
            ->paginate($perPage);
    }

    /**
     * @return Collection<int, BlogCategory>
     */
    public function allActive(): Collection
    {
        return BlogCategory::query()
            ->active()
            ->ordered()
            ->get();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): BlogCategory
    {
        return DB::transaction(fn (): BlogCategory => BlogCategory::query()->create($this->payload($data)));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(BlogCategory $category, array $data): BlogCategory
    {
        return DB::transaction(function () use ($category, $data): BlogCategory {
            $category->fill($this->payload($data, $category));
            $category->save();

            return $category->fresh();
        });
    }

    public function delete(BlogCategory $category): void
    {
        DB::transaction(static function () use ($category): void {
            $category->delete();
        });
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function payload(array $data, ?BlogCategory $category = null): array
    {
        $name = trim((string) ($data['name'] ?? $category?->name ?? ''));
        $baseSlug = trim((string) ($data['slug'] ?? '')) ?: $name;

        return [
            'name' => $name,
            'slug' => $this->generateUniqueSlug($baseSlug, $category?->id),
            'description' => $this->nullableString($data['description'] ?? $category?->description ?? null),
            'image' => $this->nullableString($data['image'] ?? $category?->image ?? null),
            'status' => (string) ($data['status'] ?? $category?->status ?? BlogCategory::STATUS_ACTIVE),
            'meta_title' => $this->nullableString($data['meta_title'] ?? $category?->meta_title ?? null),
            'meta_description' => $this->nullableString($data['meta_description'] ?? $category?->meta_description ?? null),
        ];
    }

    private function generateUniqueSlug(string $value, ?int $ignoreId = null): string
    {
        $baseSlug = Str::slug($value) ?: 'blog-category';
        $slug = $baseSlug;
        $counter = 2;

        while (
            BlogCategory::query()
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

    private function nullableString(mixed $value): ?string
    {
        if (! is_scalar($value)) {
            return null;
        }

        $value = trim((string) $value);

        return $value !== '' ? $value : null;
    }
}
