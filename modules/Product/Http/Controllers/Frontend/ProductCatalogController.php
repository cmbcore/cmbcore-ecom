<?php

declare(strict_types=1);

namespace Modules\Product\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Modules\Category\Http\Resources\CategoryResource;
use Modules\FlashSale\Services\FlashSaleService;
use Modules\Product\Http\Resources\ProductResource;
use Modules\Product\Services\ProductCatalogService;
use Modules\Review\Services\ReviewService;

class ProductCatalogController extends Controller
{
    public function __construct(
        private readonly ProductCatalogService $catalogService,
        private readonly ReviewService $reviewService,
        private readonly FlashSaleService $flashSaleService,
    ) {
    }

    public function index(Request $request): View
    {
        return theme_manager()->view($this->listingView(), $this->listingPayload($request));
    }

    public function category(Request $request, string $slug): View
    {
        return theme_manager()->view($this->listingView(), $this->listingPayload($request, $slug));
    }

    protected function listingView(): string
    {
        return 'products.index';
    }

    /**
     * @return array<string, mixed>
     */
    protected function listingPayload(Request $request, ?string $categorySlug = null): array
    {
        $filters = [
            'search' => trim((string) $request->query('search', '')),
            'category' => $categorySlug !== null
                ? trim($categorySlug)
                : trim((string) $request->query('category', '')),
            'price_min' => $request->query('price_min'),
            'price_max' => $request->query('price_max'),
            'brand' => trim((string) $request->query('brand', '')),
            'rating' => $request->query('rating'),
            'in_stock' => (bool) $request->boolean('in_stock'),
            'sort' => trim((string) $request->query('sort', 'featured')),
        ];

        $selectedCategory = $this->catalogService->selectedCategory($filters);
        $products = $this->catalogService->paginate($filters);

        return [
            'page' => [
                'title' => $selectedCategory?->name ?? theme_text('products.list_title'),
                'meta_title' => $selectedCategory?->meta_title ?: theme_text('products.list_title'),
                'meta_description' => $selectedCategory?->meta_description ?: theme_text('products.list_description'),
            ],
            'breadcrumbs' => $this->catalogService->listingBreadcrumbs($selectedCategory),
            'filters' => $filters,
            'products' => ProductResource::collection($products->getCollection())->resolve(),
            'pagination' => [
                'current_page' => $products->currentPage(),
                'last_page' => $products->lastPage(),
                'prev_url' => $products->previousPageUrl(),
                'next_url' => $products->nextPageUrl(),
            ],
            'categories' => CategoryResource::collection($this->catalogService->categoryTree())->resolve(),
            'selected_category' => $selectedCategory
                ? [
                    'id' => $selectedCategory->id,
                    'name' => $selectedCategory->name,
                    'slug' => $selectedCategory->slug,
                ]
                : null,
        ];
    }

    public function show(string $slug): View
    {
        $product = $this->catalogService->findBySlug($slug);
        $requestUser = request()->user();
        $primarySku = $product->skus->first();
        $effectivePrice = $primarySku ? $this->flashSaleService->effectivePrice($primarySku) : 0;

        return theme_manager()->view('products.show', [
            'page' => [
                'title' => $product->name,
                'meta_title' => $product->meta_title ?: $product->name,
                'meta_description' => $product->meta_description ?: ($product->short_description ?: theme_text('products.detail_description')),
            ],
            'breadcrumbs' => $this->catalogService->productBreadcrumbs($product),
            'product' => (new ProductResource($product))->resolve(),
            'related_products' => ProductResource::collection($this->catalogService->relatedProducts($product))->resolve(),
            'reviews' => $this->reviewService->approvedForProduct($product),
            'can_review' => $this->reviewService->canReview($requestUser, $product),
            'flash_sale' => $this->flashSaleService->activeSaleForProduct($product),
            'seo' => [
                'og' => [
                    'og:type' => 'product',
                    'og:title' => $product->meta_title ?: $product->name,
                    'og:description' => $product->meta_description ?: ($product->short_description ?: $product->name),
                    'og:url' => route('storefront.products.show', ['slug' => $product->slug]),
                    'og:image' => optional($product->media->first())->path ? theme_media_url($product->media->first()->path) : null,
                ],
                'schema' => [
                    '@context' => 'https://schema.org',
                    '@type' => 'Product',
                    'name' => $product->name,
                    'description' => $product->short_description ?: $product->description,
                    'sku' => $primarySku?->sku_code,
                    'offers' => [
                        '@type' => 'Offer',
                        'priceCurrency' => 'VND',
                        'price' => (string) $effectivePrice,
                        'availability' => (($primarySku?->stock_quantity ?? 0) > 0)
                            ? 'https://schema.org/InStock'
                            : 'https://schema.org/OutOfStock',
                    ],
                    'aggregateRating' => [
                        '@type' => 'AggregateRating',
                        'ratingValue' => (float) ($product->rating_value ?? 0),
                        'reviewCount' => (int) ($product->review_count ?? 0),
                    ],
                ],
            ],
        ]);
    }
}
