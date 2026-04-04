<?php

declare(strict_types=1);

namespace Modules\Category\Services;

use App\Support\SearchEscape;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Modules\Category\Models\Category;

class CategoryService
{
    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, Category>
     */
    public function paginate(array $filters = []): LengthAwarePaginator
    {
        $perPage = (int) ($filters['per_page'] ?? config('category.per_page', 15));

        return Category::query()
            ->with('parent')
            ->when(
                filled($filters['search'] ?? null),
                function (Builder $query) use ($filters): void {
                    $search = SearchEscape::like((string) $filters['search']);
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
            ->when(
                array_key_exists('parent_id', $filters) && $filters['parent_id'] !== null && $filters['parent_id'] !== '',
                static fn (Builder $query) => $query->where('parent_id', (int) $filters['parent_id']),
            )
            ->ordered()
            ->paginate($perPage);
    }

    /**
     * @return Collection<int, Category>
     */
    public function getTree(?int $excludeId = null): Collection
    {
        $excludedIds = $excludeId ? $this->getExcludedIds($excludeId) : [];

        return Category::query()
            ->roots()
            ->when(
                $excludedIds !== [],
                static fn (Builder $query) => $query->whereNotIn('id', $excludedIds),
            )
            ->with([
                'children' => fn ($query) => $this->applyTreeQuery($query, $excludedIds),
                'children.children' => fn ($query) => $this->applyTreeQuery($query, $excludedIds),
            ])
            ->ordered()
            ->get();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Category
    {
        return DB::transaction(function () use ($data): Category {
            $parent = $this->resolveParent($data['parent_id'] ?? null);
            $level = $parent ? $parent->level + 1 : 1;

            if ($level > (int) config('category.max_depth', 3)) {
                throw ValidationException::withMessages([
                    'parent_id' => [__('admin.categories.validation.depth_limit')],
                ]);
            }

            $payload = $this->fillPayload($data, $parent, $level);

            return Category::query()->create($payload);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Category $category, array $data): Category
    {
        return DB::transaction(function () use ($category, $data): Category {
            $parent = $this->resolveParent($data['parent_id'] ?? null);

            if ($parent && ($parent->id === $category->id || $this->isDescendantOf($parent, $category))) {
                throw ValidationException::withMessages([
                    'parent_id' => [__('admin.categories.validation.parent_descendant')],
                ]);
            }

            $level = $parent ? $parent->level + 1 : 1;
            $subtreeHeight = $this->getSubtreeHeight($category);
            $maxDepth = (int) config('category.max_depth', 3);

            if (($level + $subtreeHeight - 1) > $maxDepth) {
                throw ValidationException::withMessages([
                    'parent_id' => [__('admin.categories.validation.depth_limit_with_children')],
                ]);
            }

            $category->fill($this->fillPayload($data, $parent, $level, $category));
            $category->save();

            $this->syncDescendantLevels($category);

            return $category->fresh(['parent', 'children']);
        });
    }

    public function delete(Category $category): void
    {
        DB::transaction(static function () use ($category): void {
            $category->delete();
        });
    }

    private function resolveParent(mixed $parentId): ?Category
    {
        if (! $parentId) {
            return null;
        }

        return Category::query()->findOrFail((int) $parentId);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function fillPayload(array $data, ?Category $parent, int $level, ?Category $category = null): array
    {
        $name = trim((string) $data['name']);
        $baseSlug = trim((string) ($data['slug'] ?? '')) ?: $name;

        return [
            'parent_id' => $parent?->id,
            'name' => $name,
            'slug' => $this->generateUniqueSlug($baseSlug, $category?->id),
            'description' => $data['description'] ?? null,
            'image' => $data['image'] ?? null,
            'icon' => $data['icon'] ?? null,
            'position' => (int) ($data['position'] ?? 0),
            'level' => $level,
            'status' => (string) ($data['status'] ?? Category::STATUS_ACTIVE),
            'meta_title' => $data['meta_title'] ?? null,
            'meta_description' => $data['meta_description'] ?? null,
        ];
    }

    private function generateUniqueSlug(string $value, ?int $ignoreId = null): string
    {
        $baseSlug = Str::slug($value) ?: 'category';
        $slug = $baseSlug;
        $counter = 2;

        while (
            Category::query()
                ->when($ignoreId, static fn (Builder $query) => $query->where('id', '!=', $ignoreId))
                ->where('slug', $slug)
                ->exists()
        ) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    /**
     * @param  array<int, int>  $excludedIds
     */
    /**
     * @param  Builder<Category>|\Illuminate\Database\Eloquent\Relations\HasMany  $query
     * @param  array<int, int>  $excludedIds
     */
    private function applyTreeQuery($query, array $excludedIds): void
    {
        if ($excludedIds !== []) {
            $query->whereNotIn('id', $excludedIds);
        }

        $query->ordered();
    }

    /**
     * @return array<int, int>
     */
    private function getExcludedIds(int $categoryId): array
    {
        $category = Category::query()->find($categoryId);

        if (! $category) {
            return [];
        }

        return $category->descendants()->pluck('id')->prepend($category->id)->all();
    }

    private function isDescendantOf(Category $candidateParent, Category $category): bool
    {
        $currentParentId = $candidateParent->parent_id;

        while ($currentParentId) {
            if ($currentParentId === $category->id) {
                return true;
            }

            $currentParentId = Category::query()
                ->whereKey($currentParentId)
                ->value('parent_id');
        }

        return false;
    }

    private function getSubtreeHeight(Category $category): int
    {
        $children = $category->children()->get();

        if ($children->isEmpty()) {
            return 1;
        }

        return 1 + $children
            ->map(fn (Category $child): int => $this->getSubtreeHeight($child))
            ->max();
    }

    private function syncDescendantLevels(Category $category): void
    {
        foreach ($category->children()->get() as $child) {
            $expectedLevel = $category->level + 1;

            if ($child->level !== $expectedLevel) {
                $child->forceFill(['level' => $expectedLevel])->save();
            }

            $this->syncDescendantLevels($child);
        }
    }
}
