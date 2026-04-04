<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\InstalledTheme;
use App\Models\User;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Tests\TestCase;
use ZipArchive;

class ThemeManagerApiTest extends TestCase
{
    use RefreshDatabase;

    /** @var array<int, string> */
    private array $cleanupPaths = [];

    public function test_admin_user_can_list_activate_and_update_theme_configuration(): void
    {
        $user = User::query()->create([
            'name' => 'Theme Admin',
            'email' => 'themes@example.com',
            'password' => 'password',
            'role' => User::ROLE_ADMIN,
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->getJson('/api/admin/themes')
            ->assertOk()
            ->assertJsonFragment(['alias' => 'cmbcore']);

        $payload = [
            'settings' => [
                'primary_color' => '#123456',
                'secondary_color' => '#654321',
                'home_products_per_section' => 6,
                'footer_contact' => [
                    'company' => 'CMBCORE',
                    'address' => 'Ha Noi',
                    'phone' => '0123456789',
                    'email' => 'hello@example.com',
                    'gov_badge_url' => 'https://example.com/badge',
                    'gov_badge_image' => '/theme-assets/cmbcore/demo/footer-badge.webp',
                    'gov_badge_alt' => 'Badge',
                ],
                'home_hero_slides' => [
                    [
                        'desktop' => '/theme-assets/cmbcore/demo/hero-slide-1-desktop.png',
                        'mobile' => '/theme-assets/cmbcore/demo/hero-slide-1-mobile.webp',
                        'alt' => 'Hero 1',
                        'eyebrow' => 'Slide 1',
                        'title' => 'Structured settings',
                        'body' => 'The admin now stores arrays instead of JSON text blobs.',
                        'primary_label' => 'View products',
                        'primary_url' => '/san-pham',
                        'secondary_label' => 'View posts',
                        'secondary_url' => '/category/tin-tuc/',
                    ],
                ],
            ],
            'menus' => [
                [
                    'alias' => 'main_menu',
                    'items' => [
                        [
                            'label' => [
                                'vi' => 'Gioi thieu',
                                'en' => 'About',
                            ],
                            'url' => '/gioi-thieu',
                            'icon' => 'fa-solid fa-circle-info',
                            'target' => '_self',
                        ],
                    ],
                ],
                [
                    'alias' => 'footer_about_menu',
                    'items' => [
                        [
                            'label' => [
                                'vi' => 'Lien he',
                                'en' => 'Contact',
                            ],
                            'url' => '/lien-he',
                            'icon' => 'fa-solid fa-envelope',
                            'target' => '_blank',
                        ],
                    ],
                ],
            ],
        ];

        $this->actingAs($user)
            ->putJson('/api/admin/themes/cmbcore/settings', $payload)
            ->assertOk()
            ->assertJsonPath('data.settings.primary_color', '#123456')
            ->assertJsonPath('data.settings.secondary_color', '#654321')
            ->assertJsonPath('data.settings.home_products_per_section', 6)
            ->assertJsonPath('data.settings.footer_contact.company', 'CMBCORE')
            ->assertJsonPath('data.settings.home_hero_slides.0.title', 'Structured settings')
            ->assertJsonPath('data.menus.0.alias', 'main_menu')
            ->assertJsonPath('data.menus.0.items.0.label.vi', 'Gioi thieu')
            ->assertJsonPath('data.menus.1.alias', 'footer_about_menu')
            ->assertJsonPath('data.menus.1.items.0.target', '_blank');

        $this->actingAs($user)
            ->putJson('/api/admin/themes/cmbcore/activate')
            ->assertOk()
            ->assertJsonPath('data.theme.is_active', true);

        $installedTheme = InstalledTheme::query()->where('alias', 'cmbcore')->first();

        self::assertNotNull($installedTheme);
        self::assertSame('#123456', $installedTheme->settings['primary_color'] ?? null);
        self::assertSame('Gioi thieu', $installedTheme->settings['menus']['main_menu'][0]['label']['vi'] ?? null);
        self::assertSame('CMBCORE', $installedTheme->settings['footer_contact']['company'] ?? null);
        self::assertSame('Structured settings', $installedTheme->settings['home_hero_slides'][0]['title'] ?? null);
    }

    public function test_storefront_renders_configured_shared_menus(): void
    {
        $user = User::query()->create([
            'name' => 'Theme Admin',
            'email' => 'themes-storefront@example.com',
            'password' => 'password',
            'role' => User::ROLE_ADMIN,
            'is_active' => true,
        ]);

        $this->actingAs($user)->putJson('/api/admin/themes/cmbcore/settings', [
            'settings' => [
                'primary_color' => '#0f766e',
                'secondary_color' => '#334155',
            ],
            'menus' => [
                [
                    'alias' => 'main_menu',
                    'items' => [
                        [
                            'label' => [
                                'vi' => 'Bài viết',
                                'en' => 'Articles',
                            ],
                            'url' => '/bai-viet',
                            'icon' => 'fa-solid fa-newspaper',
                            'target' => '_self',
                        ],
                    ],
                ],
                [
                    'alias' => 'footer_about_menu',
                    'items' => [
                        [
                            'label' => [
                                'vi' => 'Lien he',
                                'en' => 'Contact',
                            ],
                            'url' => '/lien-he',
                            'icon' => 'fa-solid fa-envelope',
                            'target' => '_self',
                        ],
                    ],
                ],
            ],
        ])->assertOk();

        $this->get('/')
            ->assertOk()
            ->assertSee('Bài viết')
            ->assertSee('Lien he');

        $this->withCookie('cmbcore_locale', 'en')
            ->get('/')
            ->assertOk()
            ->assertSee('Articles')
            ->assertSee('Contact');
    }

    public function test_preview_route_uses_requested_theme_instead_of_active_theme(): void
    {
        $user = User::query()->create([
            'name' => 'Theme Preview Admin',
            'email' => 'themes-preview@example.com',
            'password' => 'password',
            'role' => User::ROLE_ADMIN,
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)
            ->postJson('/api/admin/themes/default/preview-session', [
                'preview_target' => 'home',
            ])
            ->assertOk();

        $previewUrl = (string) $response->json('preview_url');
        $previewPath = (string) parse_url($previewUrl, PHP_URL_PATH);
        $previewQuery = (string) parse_url($previewUrl, PHP_URL_QUERY);

        $this->get($previewPath . ($previewQuery !== '' ? '?' . $previewQuery : ''))
            ->assertOk()
            ->assertSee('sf-home__hero', false)
            ->assertDontSee('cmbcore-hero__viewport', false);
    }

    public function test_theme_assets_for_cmbcore_are_publicly_accessible(): void
    {
        $cssResponse = $this->get('/theme-assets/cmbcore/css/theme.css')
            ->assertOk();

        self::assertStringContainsString('public', (string) $cssResponse->headers->get('Cache-Control'));
        self::assertStringContainsString('max-age=31536000', (string) $cssResponse->headers->get('Cache-Control'));
        self::assertStringContainsString('immutable', (string) $cssResponse->headers->get('Cache-Control'));
        self::assertInstanceOf(BinaryFileResponse::class, $cssResponse->baseResponse);
        self::assertSame('theme.css', $cssResponse->baseResponse->getFile()->getFilename());

        $jsResponse = $this->get('/theme-assets/cmbcore/js/theme.js')
            ->assertOk();

        self::assertStringContainsString('public', (string) $jsResponse->headers->get('Cache-Control'));
        self::assertStringContainsString('max-age=31536000', (string) $jsResponse->headers->get('Cache-Control'));
        self::assertStringContainsString('immutable', (string) $jsResponse->headers->get('Cache-Control'));
        self::assertInstanceOf(BinaryFileResponse::class, $jsResponse->baseResponse);
        self::assertSame('theme.js', $jsResponse->baseResponse->getFile()->getFilename());
    }

    public function test_admin_user_can_install_theme_from_zip_package(): void
    {
        $user = User::query()->create([
            'name' => 'Theme Installer',
            'email' => 'theme-installer@example.com',
            'password' => 'password',
            'role' => User::ROLE_ADMIN,
            'is_active' => true,
        ]);

        $themeDirectory = base_path('themes/demo-installer');
        $archivePath = $this->createZipPackage('demo-installer-theme.zip', [
            'DemoInstaller/theme.json' => json_encode([
                'name' => 'Demo Installer Theme',
                'alias' => 'demo-installer',
                'version' => '1.0.0',
                'description' => 'ZIP installed theme.',
                'author' => 'Tests',
                'install' => [
                    'activate' => true,
                ],
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            'DemoInstaller/views/layouts/app.blade.php' => <<<'BLADE'
<!DOCTYPE html>
<html lang="vi">
<body>@yield('content')</body>
</html>
BLADE,
            'DemoInstaller/views/home.blade.php' => '<div>Demo theme home</div>',
        ]);

        $this->cleanupPaths[] = $themeDirectory;
        $this->cleanupPaths[] = $archivePath;

        $response = $this->actingAs($user)
            ->post('/api/admin/themes/install', [
                'package' => new UploadedFile($archivePath, 'demo-installer-theme.zip', 'application/zip', null, true),
            ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.theme.alias', 'demo-installer')
            ->assertJsonPath('data.theme.is_active', true);

        self::assertDirectoryExists($themeDirectory);
        self::assertTrue(InstalledTheme::query()->where('alias', 'demo-installer')->where('is_active', true)->exists());
    }

    protected function tearDown(): void
    {
        $files = app(Filesystem::class);

        foreach (array_reverse($this->cleanupPaths) as $path) {
            if (is_dir($path)) {
                $files->deleteDirectory($path);
            } elseif (is_file($path)) {
                @unlink($path);
            }
        }

        $this->cleanupPaths = [];

        parent::tearDown();
    }

    /**
     * @param  array<string, string>  $entries
     */
    private function createZipPackage(string $filename, array $entries): string
    {
        $path = storage_path('app/testing/' . $filename);
        $directory = dirname($path);

        if (! is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        $zip = new ZipArchive();
        $zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        foreach ($entries as $entryPath => $contents) {
            $zip->addFromString($entryPath, $contents);
        }

        $zip->close();

        return $path;
    }
}
