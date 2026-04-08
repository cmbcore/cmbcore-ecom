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
            'content_html'  => $contentHtml,
            'excerpt_html'  => $page->excerpt,
            'content_blocks' => $parsedContent['blocks'],
        ]);

        $pageUrl    = url('/' . ltrim((string) $page->slug, '/'));
        $pageTitle  = $page->title;
        $metaTitle  = $page->meta_title ?: $pageTitle;
        $metaDesc   = $page->meta_description ?: ($page->excerpt ?: theme_text('pages.detail_description'));
        $imageUrl   = !empty($page->featured_image) ? theme_media_url($page->featured_image) : null;

        $breadcrumbs = $this->catalogService->breadcrumbs($page);
        $breadcrumbItems = [];
        foreach ($breadcrumbs as $i => $crumb) {
            $breadcrumbItems[] = [
                '@type'    => 'ListItem',
                'position' => $i + 1,
                'name'     => $crumb['label'] ?? '',
                'item'     => $crumb['url'] ?? $pageUrl,
            ];
        }
        $breadcrumbItems[] = [
            '@type'    => 'ListItem',
            'position' => count($breadcrumbItems) + 1,
            'name'     => $pageTitle,
            'item'     => $pageUrl,
        ];

        $webPageSchema = [
            '@type'       => 'WebPage',
            '@id'         => $pageUrl,
            'name'        => $pageTitle,
            'description' => $metaDesc,
            'url'         => $pageUrl,
            'inLanguage'  => 'vi',
        ];

        if ($imageUrl) {
            $webPageSchema['image'] = $imageUrl;
        }

        return theme_manager()->view($this->catalogService->resolveTemplateView($page), [
            'page' => [
                'title'            => $pageTitle,
                'meta_title'       => $metaTitle,
                'meta_description' => $metaDesc,
            ],
            'breadcrumbs'  => $breadcrumbs,
            'content_page' => $pagePayload,
            'seo' => [
                'og' => [
                    'og:type'        => 'website',
                    'og:title'       => $metaTitle,
                    'og:description' => $metaDesc,
                    'og:url'         => $pageUrl,
                    'og:image'       => $imageUrl,
                ],
                'schema' => [
                    '@context' => 'https://schema.org',
                    '@graph'   => [
                        $webPageSchema,
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
