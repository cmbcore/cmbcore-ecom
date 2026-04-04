<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Modules\Blog\Http\Resources\BlogPostResource;
use Modules\Blog\Models\BlogPost;
use Modules\Banner\Services\BannerService;
use Modules\Category\Http\Resources\CategoryResource;
use Modules\Category\Models\Category;
use Modules\Product\Http\Resources\ProductResource;
use Modules\Product\Models\Product;

class StorefrontHomeService
{
    public function __construct(
        private readonly StorefrontDataReadiness $readiness,
        private readonly BannerService $bannerService,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function payload(): array
    {
        $sectionLimit = max(1, (int) theme_setting('home_products_per_section', 8));
        $postLimit = max(1, (int) theme_setting('home_posts_per_section', 4));
        $hasProducts = $this->readiness->hasProducts();
        $hasBlog = $this->readiness->hasBlog();
        $productSections = $hasProducts
            ? $this->productSections($sectionLimit)
            : [];

        return [
            'hero_slides' => $this->bannerService->activeSlides() !== []
                ? $this->bannerService->activeSlides()
                : theme_setting_json('home_hero_slides', []),
            'product_sections' => $productSections,
            'quote_cards' => theme_setting_json('home_quote_cards', []),
            'testimonials' => theme_setting_json('home_testimonials', []),
            'procedure_steps' => theme_setting_json('home_procedure_steps', []),
            'register_panel' => theme_setting_json('home_register_panel', []),
            'latest_posts' => $hasBlog
                ? BlogPostResource::collection($this->latestPosts($postLimit))->resolve()
                : [],
        ];
    }

    /**
     * @return Collection<int, Product>
     */
    private function featuredProducts(int $limit): Collection
    {
        return $this->baseProductQuery()
            ->where('is_featured', true)
            ->limit($limit)
            ->get();
    }

    /**
     * @return Collection<int, Product>
     */
    private function latestProducts(int $limit): Collection
    {
        return $this->baseProductQuery()
            ->latest('id')
            ->limit($limit)
            ->get();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function productSections(int $defaultLimit): array
    {
        $configuredSections = theme_setting_json('home_product_sections', []);

        if (! is_array($configuredSections) || $configuredSections === []) {
            $configuredSections = $this->legacyProductSectionSettings();
        }

        return collect($configuredSections)
            ->filter(static fn(mixed $section): bool => is_array($section))
            ->map(function (array $section, int $index) use ($defaultLimit): ?array {
                $type = (string) ($section['source_type'] ?? '');
                $title = trim((string) ($section['title'] ?? ''));
                $limit = max(1, (int) ($section['limit'] ?? $defaultLimit));

                return match ($type) {
                    'featured' => $this->resolveFeaturedSection($title, $limit, $index),
                    'latest' => $this->resolveLatestSection($title, $limit, $index),
                    'category' => $this->resolveCategorySection($section, $title, $limit, $index),
                    default => null,
                };
            })
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @return Collection<int, Product>
     */
    private function productsForCategory(Category $category, int $limit): Collection
    {
        $categoryIds = $category
            ->descendants()
            ->pluck('id')
            ->prepend($category->id)
            ->all();

        return $this->baseProductQuery()
            ->whereIn('category_id', $categoryIds)
            ->limit($limit)
            ->get();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function legacyProductSectionSettings(): array
    {
        $sections = [
            [
                'source_type' => 'featured',
                'title' => (string) theme_setting('home_best_sellers_title', theme_text('home.best_sellers_title')),
            ],
            [
                'source_type' => 'latest',
                'title' => (string) theme_setting('home_latest_products_title', theme_text('home.latest_products_title')),
            ],
        ];

        $legacyCategorySlugs = collect(theme_setting_json('home_category_slugs', []))
            ->filter(static fn(mixed $slug): bool => is_string($slug) && trim($slug) !== '')
            ->map(static fn(string $slug): string => trim($slug))
            ->values();

        foreach ($legacyCategorySlugs as $slug) {
            $sections[] = [
                'source_type' => 'category',
                'category_slug' => $slug,
                'title' => '',
            ];
        }

        return $sections;
    }

    /**
     * @return array<string, mixed>
     */
    private function resolveFeaturedSection(string $title, int $limit, int $index): array
    {
        return [
            'key' => 'featured-' . $index,
            'source_type' => 'featured',
            'title' => $title !== '' ? $title : theme_text('home.best_sellers_title'),
            'link_url' => theme_route_url('storefront.products.index'),
            'products' => ProductResource::collection($this->featuredProducts($limit))->resolve(),
            'category' => null,
            'banner_image_url' => null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function resolveLatestSection(string $title, int $limit, int $index): array
    {
        return [
            'key' => 'latest-' . $index,
            'source_type' => 'latest',
            'title' => $title !== '' ? $title : theme_text('home.latest_products_title'),
            'link_url' => theme_route_url('storefront.products.index'),
            'products' => ProductResource::collection($this->latestProducts($limit))->resolve(),
            'category' => null,
            'banner_image_url' => null,
        ];
    }

    /**
     * @param  array<string, mixed>  $section
     * @return array<string, mixed>|null
     */
    private function resolveCategorySection(array $section, string $title, int $limit, int $index): ?array
    {
        $category = $this->findConfiguredCategory($section);

        if (! $category instanceof Category) {
            return null;
        }

        return [
            'key' => 'category-' . $index . '-' . $category->id,
            'source_type' => 'category',
            'title' => $title !== '' ? $title : $category->name,
            'link_url' => theme_route_url('storefront.product-categories.show', ['slug' => $category->slug]),
            'products' => ProductResource::collection($this->productsForCategory($category, $limit))->resolve(),
            'category' => (new CategoryResource($category))->resolve(),
            'banner_image_url' => $category->image ? theme_media_url($category->image) : null,
        ];
    }

    /**
     * @param  array<string, mixed>  $section
     */
    private function findConfiguredCategory(array $section): ?Category
    {
        $categoryId = isset($section['category_id']) && is_scalar($section['category_id'])
            ? (int) $section['category_id']
            : 0;
        $categorySlug = isset($section['category_slug']) && is_scalar($section['category_slug'])
            ? trim((string) $section['category_slug'])
            : '';

        $query = Category::query()->active();

        /** @var Category|null $category */
        $category = $categoryId > 0
            ? $query->find($categoryId)
            : ($categorySlug !== '' ? $query->where('slug', $categorySlug)->first() : null);

        return $category;
    }

    /**
     * @return Collection<int, BlogPost>
     */
    private function latestPosts(int $limit): Collection
    {
        return BlogPost::query()
            ->published()
            ->with('category')
            ->ordered()
            ->limit($limit)
            ->get();
    }

    /**
     * @return Builder<Product>
     */
    private function baseProductQuery(): Builder
    {
        return Product::query()
            ->active()
            ->with(['category', 'skus.attributes', 'media'])
            ->withCount(['skus as sku_count', 'media as media_count'])
            ->withSum('skus as total_stock', 'stock_quantity')
            ->withMin('skus as min_price', 'price')
            ->withMax('skus as max_price', 'price')
            ->withMin('skus as min_compare_price', 'compare_price')
            ->withMax('skus as max_compare_price', 'compare_price');
    }
}
