<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Core\Localization\LocalizationManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ThemeTemplateTagTest extends TestCase
{
    use RefreshDatabase;

    public function test_frontend_template_tags_return_system_data(): void
    {
        app(LocalizationManager::class)->apply('en');

        self::assertSame((string) config('app.name'), theme_site_name());
        self::assertSame(route('storefront.home', [], false), theme_home_url());
        self::assertSame(route('locale.switch', ['locale' => 'vi'], false), theme_locale_url('vi'));
        self::assertSame('Language', theme_text('navigation.language'));
        self::assertTrue(theme_has_menu('main_menu'));
        self::assertCount(2, theme_supported_locales());

        $items = theme_menu_items('main_menu');

        self::assertNotEmpty($items);
        self::assertSame('Body care', theme_menu_label($items[0]));
        self::assertSame('/danh-muc-san-pham/cham-soc-co-the/', theme_menu_url($items[0]));
        self::assertSame('_self', theme_menu_target($items[0]));
        self::assertSame('', (string) theme_menu_icon($items[0], 'nav__icon'));
        self::assertSame('/theme-assets/cmbcore/css/theme.css?v=1.0.0', theme_asset('css/theme.css'));
        self::assertSame('/gioi-thieu', theme_url('gioi-thieu'));
        self::assertSame('https://example.com/file.css', theme_url('https://example.com/file.css'));
    }

    public function test_theme_view_context_is_available_to_theme_helpers(): void
    {
        theme_manager()->view('home', [
            'page' => [
                'title' => 'Trang thử nghiệm',
            ],
            'breadcrumbs' => [
                [
                    'label' => 'Trang chủ',
                    'url' => '/',
                ],
            ],
        ]);

        self::assertTrue(theme_has_context('page.title'));
        self::assertSame('Trang thử nghiệm', theme_context('page.title'));
        self::assertSame('Trang chủ', theme_context('breadcrumbs.0.label'));
        self::assertSame('/', theme_context('breadcrumbs.0.url'));
    }
}
