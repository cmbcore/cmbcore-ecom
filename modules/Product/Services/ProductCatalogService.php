<?php

declare(strict_types=1);

namespace Modules\Product\Services;

use App\Services\StorefrontDataReadiness;
use Illuminate\Contracts\Pagination\LengthAwarePaginator as LengthAwarePaginatorContract;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Category\Models\Category;
use Modules\Product\Models\Product;
use Modules\Search\Services\SearchService;

class ProductCatalogService
{
    public function __construct(
        private readonly StorefrontDataReadiness $readiness,
        private readonly SearchService $searchService,
    ) {
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginatorContract<int, Product>
     */
    public function paginate(array $filters = []): LengthAwarePaginatorContract
    {
        if (! $this->readiness->hasProducts()) {
            return $this->emptyPaginator();
        }

        $perPage = max(1, (int) theme_setting('products_per_page', config('product.per_page', 12)));
        $selectedCategory = $this->selectedCategory($filters);
        $searchTerm = trim((string) ($filters['search'] ?? ''));

        if ($searchTerm !== '') {
            $this->searchService->track($searchTerm);
        }

        $query = Product::query()
            ->active()
            ->with(['category', 'skus.attributes', 'media'])
            ->withCount(['skus as sku_count', 'media as media_count'])
            ->withSum('skus as total_stock', 'stock_quantity')
            ->withMin('skus as min_price', 'price')
            ->withMax('skus as max_price', 'price')
            ->withMin('skus as min_compare_price', 'compare_price')
            ->withMax('skus as max_compare_price', 'compare_price')
            ->when(
                $selectedCategory instanceof Category,
                fn (Builder $query) => $query->whereIn('category_id', $this->categoryIdsForFilter($selectedCategory)),
            )
            ->when(
                $searchTerm !== '',
                function (Builder $query) use ($filters): void {
                    $search = '%' . trim((string) $filters['search']) . '%';
                    $query->where(function (Builder $searchQuery) use ($search): void {
                        $searchQuery
                            ->where('name', 'like', $search)
                            ->orWhere('slug', 'like', $search)
                            ->orWhere('brand', 'like', $search)
                            ->orWhere('short_description', 'like', $search);
                    });
                },
            )
            ->when(
                is_numeric($filters['price_min'] ?? null),
                fn (Builder $query) => $query->having('min_price', '>=', (float) $filters['price_min']),
            )
            ->when(
                is_numeric($filters['price_max'] ?? null),
                fn (Builder $query) => $query->having('min_price', '<=', (float) $filters['price_max']),
            )
            ->when(
                filled($filters['brand'] ?? null),
                fn (Builder $query) => $query->where('brand', trim((string) $filters['brand'])),
            )
            ->when(
                ! empty($filters['in_stock']),
                fn (Builder $query) => $query->having('total_stock', '>', 0),
            )
            ->when(
                is_numeric($filters['rating'] ?? null),
                fn (Builder $query) => $query->where('rating_value', '>=', (float) $filters['rating']),
            );

        $sort = trim((string) ($filters['sort'] ?? 'featured'));

        match ($sort) {
            'price_asc' => $query->orderBy('min_price'),
            'price_desc' => $query->orderByDesc('min_price'),
            'rating' => $query->orderByDesc('rating_value'),
            'best_selling' => $query->orderByDesc('sold_count'),
            'newest' => $query->latest('id'),
            default => $query->orderByDesc('is_featured')->ordered(),
        };

        return $query->paginate($perPage)->withQueryString();
    }

    public function findBySlug(string $slug): Product
    {
        abort_unless($this->readiness->hasProducts(), 404);

        /** @var Product $product */
        $product = Product::query()
            ->active()
            ->with(['category.parent.parent', 'skus.attributes', 'media'])
            ->withCount(['skus as sku_count', 'media as media_count'])
            ->withSum('skus as total_stock', 'stock_quantity')
            ->withMin('skus as min_price', 'price')
            ->withMax('skus as max_price', 'price')
            ->withMin('skus as min_compare_price', 'compare_price')
            ->withMax('skus as max_compare_price', 'compare_price')
            ->where('slug', $slug)
            ->firstOrFail();

        $product->increment('view_count');

        return $product;
    }

    /**
     * @return Collection<int, Product>
     */
    public function relatedProducts(Product $product, int $limit = 4): Collection
    {
        if (! $this->readiness->hasProducts()) {
            return new Collection();
        }

        return Product::query()
            ->active()
            ->with(['category', 'skus.attributes', 'media'])
            ->withCount(['skus as sku_count', 'media as media_count'])
            ->withSum('skus as total_stock', 'stock_quantity')
            ->withMin('skus as min_price', 'price')
            ->withMax('skus as max_price', 'price')
            ->withMin('skus as min_compare_price', 'compare_price')
            ->withMax('skus as max_compare_price', 'compare_price')
            ->whereKeyNot($product->id)
            ->when(
                $product->category_id !== null,
                fn (Builder $query) => $query->where('category_id', $product->category_id),
            )
            ->orderByDesc('is_featured')
            ->ordered()
            ->limit($limit)
            ->get();
    }

    /**
     * @return Collection<int, Category>
     */
    public function categoryTree(): Collection
    {
        if (! $this->readiness->hasProductCategories()) {
            return new Collection();
        }

        return Category::query()
            ->roots()
            ->active()
            ->with([
                'children' => function ($query): void {
                    $query->active()->ordered();
                },
                'children.children' => function ($query): void {
                    $query->active()->ordered();
                },
            ])
            ->ordered()
            ->get();
    }

    public function selectedCategory(array $filters = []): ?Category
    {
        $slug = trim((string) ($filters['category'] ?? ''));

        if ($slug === '') {
            return null;
        }

        return $this->findCategoryBySlug($slug);
    }

    public function findCategoryBySlug(string $slug): ?Category
    {
        if (! $this->readiness->hasProductCategories()) {
            return null;
        }

        return Category::query()
            ->active()
            ->where('slug', trim($slug))
            ->first();
    }

    /**
     * @return array<int, array{label:string, url:string}>
     */
    public function listingBreadcrumbs(?Category $category = null): array
    {
        $breadcrumbs = [
            [
                'label' => theme_text('navigation.home'),
                'url' => theme_home_url(),
            ],
            [
                'label' => theme_text('navigation.products'),
                'url' => theme_route_url('storefront.products.index'),
            ],
        ];

        if (! $category instanceof Category) {
            return $breadcrumbs;
        }

        foreach ($this->categoryTrail($category) as $item) {
            $breadcrumbs[] = [
                'label' => $item->name,
                'url' => theme_route_url('storefront.product-categories.show', ['slug' => $item->slug]),
            ];
        }

        return $breadcrumbs;
    }

    /**
     * @return array<int, array{label:string, url:string}>
     */
    public function productBreadcrumbs(Product $product): array
    {
        $breadcrumbs = $this->listingBreadcrumbs($product->category);

        $breadcrumbs[] = [
            'label' => $product->name,
            'url' => theme_route_url('storefront.products.show', ['slug' => $product->slug]),
        ];

        return $breadcrumbs;
    }

    /**
     * @return array<int, Category>
     */
    private function categoryTrail(Category $category): array
    {
        $trail = [];
        $current = $category;

        while ($current instanceof Category) {
            array_unshift($trail, $current);
            $current->loadMissing('parent');
            $current = $current->parent;
        }

        return $trail;
    }

    /**
     * @return array<int, int>
     */
    private function categoryIdsForFilter(Category $category): array
    {
        return $category
            ->descendants()
            ->pluck('id')
            ->prepend($category->id)
            ->all();
    }

    /**
     * @return LengthAwarePaginatorContract<int, Product>
     */
    private function emptyPaginator(): LengthAwarePaginatorContract
    {
        $perPage = max(1, (int) theme_setting('products_per_page', config('product.per_page', 12)));
        $currentPage = max(1, (int) request()->integer('page', 1));

        return new LengthAwarePaginator(
            [],
            0,
            $perPage,
            $currentPage,
            [
                'path' => LengthAwarePaginator::resolveCurrentPath(),
                'pageName' => 'page',
            ],
        );
    }
}
