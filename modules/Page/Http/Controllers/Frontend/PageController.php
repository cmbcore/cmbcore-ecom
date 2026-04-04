<?php

declare(strict_types=1);

namespace Modules\Page\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Services\PageShortcodeService;
use App\Services\PuckRenderService;
use Illuminate\Contracts\View\View;
use Modules\Page\Http\Resources\PageResource;
use Modules\Page\Services\PageCatalogService;

class PageController extends Controller
{
    public function __construct(
        private readonly PageCatalogService $catalogService,
        private readonly PageShortcodeService $pageShortcodeService,
        private readonly PuckRenderService $puckRenderService,
    ) {
    }

    public function show(string $slug): View
    {
        $page = $this->catalogService->findBySlug($slug);

        $isBuilder = $page->template === 'builder';

        if ($isBuilder) {
            $contentHtml = $this->puckRenderService->render($page->puck_data);
            $parsedContent = ['content' => '', 'blocks' => []];
        } else {
            $contentHtml = $this->pageShortcodeService->render($page->content);
            $parsedContent = $this->pageShortcodeService->parse($page->content);
        }

        $pagePayload = array_replace((new PageResource($page))->resolve(), [
            'content_html' => $contentHtml,
            'excerpt_html' => $page->excerpt,
            'content_blocks' => $parsedContent['blocks'],
        ]);

        return theme_manager()->view($this->catalogService->resolveTemplateView($page), [
            'page' => [
                'title' => $page->title,
                'meta_title' => $page->meta_title ?: $page->title,
                'meta_description' => $page->meta_description ?: ($page->excerpt ?: theme_text('pages.detail_description')),
            ],
            'breadcrumbs' => $this->catalogService->breadcrumbs($page),
            'content_page' => $pagePayload,
        ]);
    }
}
