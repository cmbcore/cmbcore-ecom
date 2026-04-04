<?php

declare(strict_types=1);

namespace Modules\Page\Services;

use App\Core\Plugin\HookManager;
use App\Core\Theme\ThemeManager;
use App\Services\PageShortcodeService;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Page\Models\Page;

class PageService
{
    public function __construct(
        private readonly HookManager $hookManager,
        private readonly ThemeManager $themeManager,
        private readonly PageShortcodeService $pageShortcodeService,
    ) {
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, Page>
     */
    public function paginate(array $filters = []): LengthAwarePaginator
    {
        $perPage = (int) ($filters['per_page'] ?? config('page.per_page', 15));

        return Page::query()
            ->when(
                filled($filters['search'] ?? null),
                function (Builder $query) use ($filters): void {
                    $search = '%' . trim((string) $filters['search']) . '%';
                    $query->where(function (Builder $searchQuery) use ($search): void {
                        $searchQuery
                            ->where('title', 'like', $search)
                            ->orWhere('slug', 'like', $search)
                            ->orWhere('excerpt', 'like', $search);
                    });
                },
            )
            ->when(
                filled($filters['status'] ?? null),
                static fn (Builder $query) => $query->where('status', (string) $filters['status']),
            )
            ->when(
                filled($filters['template'] ?? null),
                static fn (Builder $query) => $query->where('template', (string) $filters['template']),
            )
            ->ordered()
            ->paginate($perPage);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Page
    {
        $data = $this->hookManager->applyFilter('page.creating', $data);

        return DB::transaction(function () use ($data): Page {
            $page = Page::query()->create($this->payload($data));
            $page = $page->fresh();

            $this->hookManager->fire('page.created', $page);

            return $page;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Page $page, array $data): Page
    {
        $data = $this->hookManager->applyFilter('page.updating', $data, $page);

        return DB::transaction(function () use ($page, $data): Page {
            $page->fill($this->payload($data, $page));
            $page->save();

            $page = $page->fresh();
            $this->hookManager->fire('page.updated', $page);

            return $page;
        });
    }

    public function delete(Page $page): void
    {
        DB::transaction(function () use ($page): void {
            $pageId = $page->id;
            $page->delete();

            $this->hookManager->fire('page.deleted', $pageId);
        });
    }

    /**
     * @return array<int, array{name:string, label:string}>
     */
    public function templates(): array
    {
        $templates = collect($this->themeManager->getActive()->getTemplates()['page'] ?? [])
            ->map(static function (mixed $template): ?array {
                if (! is_array($template)) {
                    return null;
                }

                $name = trim((string) ($template['name'] ?? ''));

                if ($name === '') {
                    return null;
                }

                return [
                    'name' => $name,
                    'label' => trim((string) ($template['label'] ?? $name)) ?: $name,
                ];
            })
            ->filter()
            ->values()
            ->all();

        $templates = $templates !== []
            ? $templates
            : [['name' => (string) config('page.default_template', 'default'), 'label' => 'Default']];

        // Always offer the Puck page builder template
        $hasBuilder = collect($templates)->contains(fn (array $t): bool => ($t['name'] ?? '') === 'builder');

        if (! $hasBuilder) {
            $templates[] = ['name' => 'builder', 'label' => 'Trình thiết kế (Builder)'];
        }

        return $templates;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function payload(array $data, ?Page $page = null): array
    {
        $title = trim((string) ($data['title'] ?? $page?->title ?? ''));
        $baseSlug = trim((string) ($data['slug'] ?? '')) ?: $title;
        $status = (string) ($data['status'] ?? $page?->status ?? Page::STATUS_DRAFT);
        $publishedAt = $this->resolvePublishedAt($data, $status, $page);
        $template = trim((string) ($data['template'] ?? $page?->template ?? ''));
        $templateNames = collect($this->templates())->pluck('name')->all();

        $isBuilder = $template === 'builder';

        return [
            'title' => $title,
            'slug' => $this->generateUniqueSlug($baseSlug, $page?->id),
            'template' => in_array($template, $templateNames, true)
                ? $template
                : (string) config('page.default_template', 'default'),
            'featured_image' => $this->normalizeNullableString($data['featured_image'] ?? $page?->featured_image ?? null),
            'excerpt' => $this->normalizeNullableString($data['excerpt'] ?? $page?->excerpt ?? null),
            'content' => $isBuilder
                ? null
                : $this->normalizeNullableString($this->pageShortcodeService->compile(
                    (string) ($data['content'] ?? $page?->content ?? ''),
                    is_array($data['content_blocks'] ?? null) ? $data['content_blocks'] : [],
                )),
            'puck_data' => $isBuilder
                ? $this->decodePuckData($data['puck_data'] ?? $page?->puck_data ?? null)
                : null,
            'status' => $status,
            'published_at' => $publishedAt,
            'meta_title' => $this->normalizeNullableString($data['meta_title'] ?? $page?->meta_title ?? null),
            'meta_description' => $this->normalizeNullableString($data['meta_description'] ?? $page?->meta_description ?? null),
            'meta_keywords' => $this->normalizeNullableString($data['meta_keywords'] ?? $page?->meta_keywords ?? null),
        ];
    }

    private function decodePuckData(mixed $value): ?array
    {
        if (is_array($value)) {
            return $value;
        }

        if (is_string($value) && trim($value) !== '') {
            $decoded = json_decode($value, true);

            return is_array($decoded) ? $decoded : null;
        }

        return null;
    }

    private function resolvePublishedAt(array $data, string $status, ?Page $page = null): ?Carbon
    {
        if (array_key_exists('published_at', $data)) {
            $value = $data['published_at'];

            if ($value === null || $value === '') {
                return $status === Page::STATUS_PUBLISHED
                    ? ($page?->published_at ? Carbon::parse($page->published_at) : now())
                    : null;
            }

            return Carbon::parse((string) $value);
        }

        if ($status === Page::STATUS_PUBLISHED) {
            return $page?->published_at ? Carbon::parse($page->published_at) : now();
        }

        return $page?->published_at ? Carbon::parse($page->published_at) : null;
    }

    private function generateUniqueSlug(string $value, ?int $ignoreId = null): string
    {
        $baseSlug = Str::slug($value) ?: 'page';
        $slug = $baseSlug;
        $counter = 2;

        while (
            Page::query()
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
}
