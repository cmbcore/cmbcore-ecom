<?php

declare(strict_types=1);

namespace Modules\Blog\Http\Controllers\Frontend;

use App\Support\ContentOutline;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Modules\Blog\Http\Resources\BlogCategoryResource;
use Modules\Blog\Http\Resources\BlogPostResource;
use Modules\Blog\Services\BlogCatalogService;

class BlogController extends Controller
{
    public function __construct(
        private readonly BlogCatalogService $catalogService,
        private readonly ContentOutline $contentOutline,
    ) {
    }

    public function index(Request $request): View
    {
        return $this->renderListing($request);
    }

    public function category(Request $request, string $slug): View
    {
        return $this->renderListing($request, $slug);
    }

    private function renderListing(Request $request, ?string $categorySlug = null): View
    {
        $filters = [
            'search'   => trim((string) $request->query('search', '')),
            'category' => $categorySlug !== null
                ? trim($categorySlug)
                : trim((string) $request->query('category', '')),
        ];
        $selectedCategory = $this->catalogService->selectedCategory($filters);
        $posts            = $this->catalogService->paginate($filters);

        $pageTitle    = $selectedCategory?->name ?? theme_text('blog.list_title');
        $pageMetaTitle = $selectedCategory?->meta_title ?: $pageTitle;
        $pageMetaDesc  = $selectedCategory?->meta_description ?: theme_text('blog.list_description');
        $canonicalUrl  = $selectedCategory
            ? route('storefront.blog.categories.show', ['slug' => $selectedCategory->slug])
            : route('storefront.blog.index');

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
        $breadcrumbItems[] = [
            '@type'    => 'ListItem',
            'position' => count($breadcrumbItems) + 1,
            'name'     => $pageTitle,
            'item'     => $canonicalUrl,
        ];

        return theme_manager()->view('blog.index', [
            'page' => [
                'title'            => $pageTitle,
                'meta_title'       => $pageMetaTitle,
                'meta_description' => $pageMetaDesc,
            ],
            'breadcrumbs'      => $breadcrumbs,
            'filters'          => $filters,
            'posts'            => BlogPostResource::collection($posts->getCollection())->resolve(),
            'categories'       => BlogCategoryResource::collection($this->catalogService->categories())->resolve(),
            'selected_category' => $selectedCategory
                ? ['id' => $selectedCategory->id, 'name' => $selectedCategory->name, 'slug' => $selectedCategory->slug]
                : null,
            'recent_posts'     => BlogPostResource::collection($this->catalogService->recentPosts())->resolve(),
            'pagination'       => [
                'current_page' => $posts->currentPage(),
                'last_page'    => $posts->lastPage(),
                'prev_url'     => $posts->previousPageUrl(),
                'next_url'     => $posts->nextPageUrl(),
            ],
            'seo' => [
                'og' => [
                    'og:type'        => 'website',
                    'og:title'       => $pageMetaTitle,
                    'og:description' => $pageMetaDesc,
                    'og:url'         => $canonicalUrl,
                ],
                'schema' => [
                    '@context' => 'https://schema.org',
                    '@graph'   => [
                        [
                            '@type'       => 'Blog',
                            '@id'         => $canonicalUrl,
                            'name'        => $pageTitle,
                            'description' => $pageMetaDesc,
                            'url'         => $canonicalUrl,
                            'inLanguage'  => 'vi',
                        ],
                        [
                            '@type'           => 'BreadcrumbList',
                            'itemListElement' => $breadcrumbItems,
                        ],
                    ],
                ],
            ],
        ]);
    }

    public function show(string $slug): View
    {
        $post    = $this->catalogService->findBySlug($slug);
        $content = $this->contentOutline->decorate($post->content);
        $postPayload = array_replace((new BlogPostResource($post))->resolve(), [
            'content_html' => $content['html'],
        ]);

        $postUrl     = route('storefront.blog.show', ['slug' => $post->slug]);
        $imagePath   = $post->featured_image ?? null;
        $imageUrl    = $imagePath ? theme_media_url($imagePath) : null;
        $publishedAt = $post->published_at?->toIso8601String() ?? now()->toIso8601String();
        $updatedAt   = $post->updated_at?->toIso8601String()   ?? $publishedAt;

        $breadcrumbs     = $this->catalogService->postBreadcrumbs($post);
        $breadcrumbItems = [];
        foreach ($breadcrumbs as $i => $crumb) {
            $breadcrumbItems[] = [
                '@type'    => 'ListItem',
                'position' => $i + 1,
                'name'     => $crumb['label'] ?? '',
                'item'     => $crumb['url'] ?? $postUrl,
            ];
        }
        $breadcrumbItems[] = [
            '@type'    => 'ListItem',
            'position' => count($breadcrumbItems) + 1,
            'name'     => $post->title,
            'item'     => $postUrl,
        ];

        $articleSchema = [
            '@type'            => 'BlogPosting',
            '@id'              => $postUrl,
            'headline'         => $post->title,
            'description'      => $post->meta_description ?: ($post->excerpt ?: $post->title),
            'url'              => $postUrl,
            'datePublished'    => $publishedAt,
            'dateModified'     => $updatedAt,
            'inLanguage'       => 'vi',
            'publisher'        => [
                '@type' => 'Organization',
                'name'  => theme_site_name(),
                'url'   => url('/'),
            ],
            'author' => [
                '@type' => 'Person',
                'name'  => $post->author_name ?? theme_site_name(),
            ],
            'mainEntityOfPage' => [
                '@type' => 'WebPage',
                '@id'   => $postUrl,
            ],
        ];

        if ($imageUrl) {
            $articleSchema['image'] = [
                '@type' => 'ImageObject',
                'url'   => $imageUrl,
            ];
        }

        return theme_manager()->view('blog.show', [
            'page' => [
                'title'            => $post->title,
                'meta_title'       => $post->meta_title ?: $post->title,
                'meta_description' => $post->meta_description ?: ($post->excerpt ?: theme_text('blog.detail_description')),
            ],
            'breadcrumbs'  => $breadcrumbs,
            'post'         => $postPayload,
            'toc'          => $content['toc'],
            'categories'   => BlogCategoryResource::collection($this->catalogService->categories())->resolve(),
            'recent_posts' => BlogPostResource::collection($this->catalogService->recentPosts())->resolve(),
            'related_posts' => BlogPostResource::collection($this->catalogService->relatedPosts($post))->resolve(),
            'seo' => [
                'og' => [
                    'og:type'        => 'article',
                    'og:title'       => $post->meta_title ?: $post->title,
                    'og:description' => $post->meta_description ?: ($post->excerpt ?: $post->title),
                    'og:url'         => $postUrl,
                    'og:image'       => $imageUrl,
                ],
                'schema' => [
                    '@context' => 'https://schema.org',
                    '@graph'   => [
                        $articleSchema,
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
