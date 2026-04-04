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
            'search' => trim((string) $request->query('search', '')),
            'category' => $categorySlug !== null
                ? trim($categorySlug)
                : trim((string) $request->query('category', '')),
        ];
        $selectedCategory = $this->catalogService->selectedCategory($filters);
        $posts = $this->catalogService->paginate($filters);

        return theme_manager()->view('blog.index', [
            'page' => [
                'title' => $selectedCategory?->name ?? theme_text('blog.list_title'),
                'meta_title' => $selectedCategory?->meta_title ?: theme_text('blog.list_title'),
                'meta_description' => $selectedCategory?->meta_description ?: theme_text('blog.list_description'),
            ],
            'breadcrumbs' => $this->catalogService->listingBreadcrumbs($selectedCategory),
            'filters' => $filters,
            'posts' => BlogPostResource::collection($posts->getCollection())->resolve(),
            'categories' => BlogCategoryResource::collection($this->catalogService->categories())->resolve(),
            'selected_category' => $selectedCategory
                ? [
                    'id' => $selectedCategory->id,
                    'name' => $selectedCategory->name,
                    'slug' => $selectedCategory->slug,
                ]
                : null,
            'recent_posts' => BlogPostResource::collection($this->catalogService->recentPosts())->resolve(),
            'pagination' => [
                'current_page' => $posts->currentPage(),
                'last_page' => $posts->lastPage(),
                'prev_url' => $posts->previousPageUrl(),
                'next_url' => $posts->nextPageUrl(),
            ],
        ]);
    }

    public function show(string $slug): View
    {
        $post = $this->catalogService->findBySlug($slug);
        $content = $this->contentOutline->decorate($post->content);
        $postPayload = array_replace((new BlogPostResource($post))->resolve(), [
            'content_html' => $content['html'],
        ]);

        return theme_manager()->view('blog.show', [
            'page' => [
                'title' => $post->title,
                'meta_title' => $post->meta_title ?: $post->title,
                'meta_description' => $post->meta_description ?: ($post->excerpt ?: theme_text('blog.detail_description')),
            ],
            'breadcrumbs' => $this->catalogService->postBreadcrumbs($post),
            'post' => $postPayload,
            'toc' => $content['toc'],
            'categories' => BlogCategoryResource::collection($this->catalogService->categories())->resolve(),
            'recent_posts' => BlogPostResource::collection($this->catalogService->recentPosts())->resolve(),
            'related_posts' => BlogPostResource::collection($this->catalogService->relatedPosts($post))->resolve(),
        ]);
    }
}
