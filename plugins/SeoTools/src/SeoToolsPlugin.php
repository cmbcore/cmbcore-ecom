<?php

declare(strict_types=1);

namespace Plugins\SeoTools;

use App\Core\Plugin\Contracts\PluginInterface;
use App\Core\Plugin\HookManager;
use App\Services\SettingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Modules\Blog\Models\BlogPost;
use Modules\Category\Models\Category;
use Modules\Page\Models\Page;
use Modules\Product\Models\Product;

class SeoToolsPlugin implements PluginInterface
{
    private static bool $routesRegistered = false;

    public function boot(HookManager $hooks): void
    {
        app()->singleton(SeoToolsService::class, fn ($app): SeoToolsService => new SeoToolsService(
            $app->make(SettingService::class),
        ));

        View::addNamespace('seo-tools-plugin', dirname(__DIR__) . '/resources/views');

        $service = app(SeoToolsService::class);

        $hooks->register('theme.head', fn (): string => $service->renderHeadPayload());
        $hooks->register('system.settings.updated', function () use ($service): void {
            $service->syncRobotsFile();
        });

        $this->registerRoutes();

        if (! is_file(public_path('robots.txt'))) {
            $service->syncRobotsFile();
        }
    }

    public function activate(): void
    {
        (new SeoToolsService(app(SettingService::class)))->syncRobotsFile();
    }

    public function deactivate(): void
    {
    }

    public function uninstall(): void
    {
    }

    private function registerRoutes(): void
    {
        if (self::$routesRegistered) {
            return;
        }

        self::$routesRegistered = true;

        Route::middleware('web')->group(function (): void {
            Route::get('/sitemap.xml', function (): Response {
                $service = app(SeoToolsService::class);
                $xml = view('seo-tools-plugin::sitemap', ['urls' => $service->sitemapUrls()])->render();

                return response($xml, 200, ['Content-Type' => 'application/xml']);
            })->name('storefront.sitemap');
        });

        Route::prefix('api/admin/seo-tools')
            ->middleware(['api', 'auth:sanctum', 'admin'])
            ->group(function (): void {
                Route::get('/', function (): JsonResponse {
                    return response()->json([
                        'success' => true,
                        'data' => app(SeoToolsService::class)->overview(),
                    ]);
                });
            });
    }
}

class SeoToolsService
{
    public function __construct(
        private readonly SettingService $settingService,
    ) {
    }

    public function renderHeadPayload(): string
    {
        $og     = theme_context('seo.og', []);
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

        // Build page-specific schema graph
        $graph = [];
        if (is_array($schema) && $schema !== []) {
            // If schema has @graph, merge nodes; otherwise treat schema as single node
            if (isset($schema['@graph']) && is_array($schema['@graph'])) {
                $graph = $schema['@graph'];
            } else {
                $graph[] = $schema;
            }
        }

        // Always add Organization + WebSite schema (global)
        $siteName  = theme_site_name();
        $homeUrl   = url('/');
        $logoImage = theme_media_url((string) theme_setting('logo_image', ''), '');
        $facebook  = (string) theme_setting('facebook_url', '');

        $sameAs = array_values(array_filter([$facebook]));

        $graph[] = [
            '@type'  => 'WebSite',
            '@id'    => $homeUrl . '#website',
            'name'   => $siteName,
            'url'    => $homeUrl,
            'inLanguage' => 'vi',
            'potentialAction' => [
                '@type'       => 'SearchAction',
                'target'      => [
                    '@type'       => 'EntryPoint',
                    'urlTemplate' => url('/san-pham') . '?search={search_term_string}',
                ],
                'query-input' => 'required name=search_term_string',
            ],
        ];

        $orgSchema = [
            '@type' => 'Organization',
            '@id'   => $homeUrl . '#organization',
            'name'  => $siteName,
            'url'   => $homeUrl,
        ];

        if ($logoImage !== '') {
            $orgSchema['logo'] = [
                '@type' => 'ImageObject',
                'url'   => $logoImage,
            ];
        }

        if (! empty($sameAs)) {
            $orgSchema['sameAs'] = $sameAs;
        }

        // Contact from footer settings
        $contact = theme_setting_json('footer_contact', []);
        if (! empty($contact['phone'])) {
            $orgSchema['contactPoint'] = [
                '@type'       => 'ContactPoint',
                'telephone'   => (string) $contact['phone'],
                'contactType' => 'customer service',
            ];
        }
        if (! empty($contact['address'])) {
            $orgSchema['address'] = [
                '@type'           => 'PostalAddress',
                'streetAddress'   => (string) $contact['address'],
                'addressLocality' => 'Việt Nam',
            ];
        }

        $graph[] = $orgSchema;

        if (! empty($graph)) {
            $output .= '<script type="application/ld+json">' . json_encode(
                ['@context' => 'https://schema.org', '@graph' => $graph],
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
