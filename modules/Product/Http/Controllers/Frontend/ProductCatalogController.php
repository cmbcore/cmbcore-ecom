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
            'search'    => trim((string) $request->query('search', '')),
            'category'  => $categorySlug !== null
                ? trim($categorySlug)
                : trim((string) $request->query('category', '')),
            'price_min' => $request->query('price_min'),
            'price_max' => $request->query('price_max'),
            'brand'     => trim((string) $request->query('brand', '')),
            'rating'    => $request->query('rating'),
            'in_stock'  => (bool) $request->boolean('in_stock'),
            'sort'      => trim((string) $request->query('sort', 'featured')),
        ];

        $selectedCategory = $this->catalogService->selectedCategory($filters);
        $products = $this->catalogService->paginate($filters);

        $pageTitle       = $selectedCategory?->name ?? theme_text('products.list_title');
        $pageMetaTitle   = $selectedCategory?->meta_title   ?: $pageTitle;
        $pageMetaDesc    = $selectedCategory?->meta_description ?: theme_text('products.list_description');
        $canonicalUrl    = $selectedCategory
            ? route('storefront.product-categories.show', ['slug' => $selectedCategory->slug])
            : route('storefront.products.index');

        // BreadcrumbList schema
        $breadcrumbs     = $this->catalogService->listingBreadcrumbs($selectedCategory);
        $breadcrumbItems = [];
        foreach ($breadcrumbs as $i => $crumb) {
            $breadcrumbItems[] = [
                '@type'    => 'ListItem',
                'position' => $i + 1,
                'name'     => $crumb['label'] ?? '',
                'item'     => $crumb['url'] ?? $canonicalUrl,
            ];
        }
        // Add current page as last breadcrumb item if not already
        if (empty($breadcrumbItems) || end($breadcrumbItems)['item'] !== $canonicalUrl) {
            $breadcrumbItems[] = [
                '@type'    => 'ListItem',
                'position' => count($breadcrumbItems) + 1,
                'name'     => $pageTitle,
                'item'     => $canonicalUrl,
            ];
        }

        return [
            'page' => [
                'title'            => $pageTitle,
                'meta_title'       => $pageMetaTitle,
                'meta_description' => $pageMetaDesc,
            ],
            'breadcrumbs'      => $breadcrumbs,
            'filters'          => $filters,
            'products'         => ProductResource::collection($products->getCollection())->resolve(),
            'pagination'       => [
                'current_page' => $products->currentPage(),
                'last_page'    => $products->lastPage(),
                'prev_url'     => $products->previousPageUrl(),
                'next_url'     => $products->nextPageUrl(),
            ],
            'categories'       => CategoryResource::collection($this->catalogService->categoryTree())->resolve(),
            'selected_category' => $selectedCategory
                ? [
                    'id'   => $selectedCategory->id,
                    'name' => $selectedCategory->name,
                    'slug' => $selectedCategory->slug,
                ]
                : null,
            'seo' => [
                'og' => [
                    'og:type'        => 'website',
                    'og:title'       => $pageMetaTitle,
                    'og:description' => $pageMetaDesc,
                    'og:url'         => $canonicalUrl,
                ],
                'schema' => [
                    '@context'  => 'https://schema.org',
                    '@graph'    => [
                        [
                            '@type'           => 'CollectionPage',
                            '@id'             => $canonicalUrl,
                            'name'            => $pageTitle,
                            'description'     => $pageMetaDesc,
                            'url'             => $canonicalUrl,
                            'inLanguage'      => 'vi',
                            'breadcrumb'      => [
                                '@type'           => 'BreadcrumbList',
                                'itemListElement' => $breadcrumbItems,
                            ],
                        ],
                        [
                            '@type' => 'BreadcrumbList',
                            'itemListElement' => $breadcrumbItems,
                        ],
                    ],
                ],
            ],
        ];
    }

    public function show(string $slug): View
    {
        $product     = $this->catalogService->findBySlug($slug);
        $requestUser = request()->user();
        $primarySku  = $product->skus->first();
        $effectivePrice = $primarySku ? $this->flashSaleService->effectivePrice($primarySku) : 0;

        $productUrl  = route('storefront.products.show', ['slug' => $product->slug]);
        $images      = $product->media->map(fn ($m) => theme_media_url($m->path))->filter()->values()->all();
        $imageFirst  = $images[0] ?? null;

        $breadcrumbs     = $this->catalogService->productBreadcrumbs($product);
        $breadcrumbItems = [];
        foreach ($breadcrumbs as $i => $crumb) {
            $breadcrumbItems[] = [
                '@type'    => 'ListItem',
                'position' => $i + 1,
                'name'     => $crumb['label'] ?? '',
                'item'     => $crumb['url'] ?? $productUrl,
            ];
        }
        // Last crumb = product itself
        $breadcrumbItems[] = [
            '@type'    => 'ListItem',
            'position' => count($breadcrumbItems) + 1,
            'name'     => $product->name,
            'item'     => $productUrl,
        ];

        // Build reviews schema
        $reviews        = $this->reviewService->approvedForProduct($product);
        $reviewsSchema  = [];
        foreach (array_slice($reviews, 0, 10) as $review) {
            $reviewsSchema[] = [
                '@type'         => 'Review',
                'author'        => ['@type' => 'Person', 'name' => $review['author_name'] ?? 'Khách hàng'],
                'reviewRating'  => [
                    '@type'       => 'Rating',
                    'ratingValue' => (int) ($review['rating'] ?? 5),
                    'bestRating'  => 5,
                ],
                'reviewBody'    => $review['content'] ?? '',
                'datePublished' => $review['created_at'] ?? now()->toDateString(),
            ];
        }

        $productSchema = [
            '@type'       => 'Product',
            '@id'         => $productUrl,
            'name'        => $product->name,
            'description' => $product->short_description ?: $product->description,
            'sku'         => $primarySku?->sku_code,
            'url'         => $productUrl,
            'brand'       => [
                '@type' => 'Brand',
                'name'  => theme_site_name(),
            ],
            'offers' => [
                '@type'           => 'Offer',
                'priceCurrency'   => 'VND',
                'price'           => (string) $effectivePrice,
                'availability'    => (($primarySku?->stock_quantity ?? 0) > 0)
                    ? 'https://schema.org/InStock'
                    : 'https://schema.org/OutOfStock',
                'url'             => $productUrl,
                'seller'          => ['@type' => 'Organization', 'name' => theme_site_name()],
            ],
        ];

        if (!empty($images)) {
            $productSchema['image'] = count($images) === 1 ? $images[0] : $images;
        }

        if ((float) ($product->rating_value ?? 0) > 0 && (int) ($product->review_count ?? 0) > 0) {
            $productSchema['aggregateRating'] = [
                '@type'       => 'AggregateRating',
                'ratingValue' => (float) $product->rating_value,
                'reviewCount' => (int) $product->review_count,
                'bestRating'  => 5,
                'worstRating' => 1,
            ];
        }

        if (!empty($reviewsSchema)) {
            $productSchema['review'] = $reviewsSchema;
        }

        return theme_manager()->view('products.show', [
            'page' => [
                'title'            => $product->name,
                'meta_title'       => $product->meta_title ?: $product->name,
                'meta_description' => $product->meta_description ?: ($product->short_description ?: theme_text('products.detail_description')),
            ],
            'breadcrumbs'     => $breadcrumbs,
            'product'         => (new ProductResource($product))->resolve(),
            'related_products' => ProductResource::collection($this->catalogService->relatedProducts($product))->resolve(),
            'reviews'         => $reviews,
            'can_review'      => $this->reviewService->canReview($requestUser, $product),
            'flash_sale'      => $this->flashSaleService->activeSaleForProduct($product),
            'seo' => [
                'og' => [
                    'og:type'        => 'product',
                    'og:title'       => $product->meta_title ?: $product->name,
                    'og:description' => $product->meta_description ?: ($product->short_description ?: $product->name),
                    'og:url'         => $productUrl,
                    'og:image'       => $imageFirst,
                ],
                'schema' => [
                    '@context' => 'https://schema.org',
                    '@graph'   => [
                        $productSchema,
                        [
                            '@type'           => 'BreadcrumbList',
                            'itemListElement' => $breadcrumbItems,
                        ],
                    ],
                ],
            ],
        ]);
    }
}
