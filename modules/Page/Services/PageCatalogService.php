<?php

declare(strict_types=1);

namespace Modules\Page\Services;

use App\Core\Theme\ThemeManager;
use App\Services\StorefrontDataReadiness;
use Modules\Page\Models\Page;
use RuntimeException;

class PageCatalogService
{
    public function __construct(
        private readonly ThemeManager $themeManager,
        private readonly StorefrontDataReadiness $readiness,
    ) {
    }

    public function findBySlug(string $slug): Page
    {
        abort_unless($this->readiness->hasPages(), 404);

        /** @var Page $page */
        $page = Page::query()
            ->published()
            ->where('slug', $slug)
            ->firstOrFail();

        $page->increment('view_count');

        return $page;
    }

    /**
     * @return array<int, array{label:string, url:string}>
     */
    public function breadcrumbs(Page $page): array
    {
        return [
            [
                'label' => theme_text('navigation.home'),
                'url' => theme_home_url(),
            ],
            [
                'label' => $page->title,
                'url' => theme_url($page->slug),
            ],
        ];
    }

    public function resolveTemplateView(Page $page): string
    {
        $template = trim($page->template) !== '' ? trim($page->template) : (string) config('page.default_template', 'default');

        try {
            $this->themeManager->viewName('pages.' . $template);

            return 'pages.' . $template;
        } catch (RuntimeException) {
            return 'pages.default';
        }
    }
}
