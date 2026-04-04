<?php

declare(strict_types=1);

namespace Modules\SeoTools\Services;

use App\Services\SettingService;
use Modules\Blog\Models\BlogPost;
use Modules\Category\Models\Category;
use Modules\Page\Models\Page;
use Modules\Product\Models\Product;

class SeoToolsService
{
    public function __construct(
        private readonly SettingService $settingService,
    ) {
    }

    public function renderHeadPayload(): string
    {
        $og = theme_context('seo.og', []);
        $schema = theme_context('seo.schema');
        $output = '';

        if (is_array($og)) {
            foreach ($og as $property => $value) {
                if (! is_scalar($value) || trim((string) $value) === '') {
                    continue;
                }

                $output .= '<meta property="' . e((string) $property) . '" content="' . e((string) $value) . '">' . PHP_EOL;
            }
        }

        if (is_array($schema) && $schema !== []) {
            $output .= '<script type="application/ld+json">' . json_encode(
                $schema,
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR,
            ) . '</script>' . PHP_EOL;
        }

        return $output;
    }

    /**
     * @return array<string, mixed>
     */
    public function overview(): array
    {
        return [
            'sitemap_url' => url('/sitemap.xml'),
            'robots_path' => public_path('robots.txt'),
            'robots_content' => (string) $this->settingService->get('seo', 'robots_content', "User-agent: *\nAllow: /\nSitemap: /sitemap.xml"),
        ];
    }

    public function syncRobotsFile(): void
    {
        file_put_contents(
            public_path('robots.txt'),
            (string) $this->settingService->get('seo', 'robots_content', "User-agent: *\nAllow: /\nSitemap: /sitemap.xml"),
        );
    }

    /**
     * @return array<int, array<string, string>>
     */
    public function sitemapUrls(): array
    {
        $urls = [
            ['loc' => url('/'), 'lastmod' => now()->toAtomString()],
        ];

        foreach (Product::query()->active()->get(['slug', 'updated_at']) as $product) {
            $urls[] = [
                'loc' => route('storefront.products.show', ['slug' => $product->slug]),
                'lastmod' => $product->updated_at?->toAtomString() ?? now()->toAtomString(),
            ];
        }

        foreach (Category::query()->active()->get(['slug', 'updated_at']) as $category) {
            $urls[] = [
                'loc' => route('storefront.product-categories.show', ['slug' => $category->slug]),
                'lastmod' => $category->updated_at?->toAtomString() ?? now()->toAtomString(),
            ];
        }

        foreach (BlogPost::query()->published()->get(['slug', 'updated_at']) as $post) {
            $urls[] = [
                'loc' => route('storefront.blog.show', ['slug' => $post->slug]),
                'lastmod' => $post->updated_at?->toAtomString() ?? now()->toAtomString(),
            ];
        }

        foreach (Page::query()->published()->get(['slug', 'updated_at']) as $page) {
            $urls[] = [
                'loc' => url('/' . ltrim((string) $page->slug, '/')),
                'lastmod' => $page->updated_at?->toAtomString() ?? now()->toAtomString(),
            ];
        }

        return $urls;
    }
}
