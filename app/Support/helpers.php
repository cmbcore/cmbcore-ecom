<?php

declare(strict_types=1);

use App\Core\Localization\LocalizationManager;
use App\Core\Theme\ThemeManager;
use App\Core\Theme\ThemeViewContext;
use App\Support\MediaUrl;
use Illuminate\Foundation\Vite;
use Illuminate\Support\HtmlString;

if (! function_exists('theme_manager')) {
    function theme_manager(): ThemeManager
    {
        return app(ThemeManager::class);
    }
}

if (! function_exists('theme_view')) {
    function theme_view(string $name): string
    {
        return theme_manager()->viewName($name);
    }
}

if (! function_exists('theme_layout')) {
    function theme_layout(string $name = 'app'): string
    {
        return theme_manager()->viewName('layouts.' . $name);
    }
}

if (! function_exists('theme_view_context')) {
    function theme_view_context(): ThemeViewContext
    {
        return app(ThemeViewContext::class);
    }
}

if (! function_exists('theme_context')) {
    function theme_context(?string $key = null, mixed $default = null): mixed
    {
        return theme_view_context()->get($key, $default);
    }
}

if (! function_exists('theme_has_context')) {
    function theme_has_context(string $key): bool
    {
        return theme_view_context()->has($key);
    }
}

if (! function_exists('theme_asset')) {
    function theme_asset(string $path): string
    {
        $path = ltrim($path, '/');

        if (app('router')->has('theme.assets')) {
            $theme = theme_manager()->getActive();

            return route('theme.assets', [
                'theme' => $theme->getAlias(),
                'path' => $path,
            ], false) . '?v=' . urlencode($theme->getVersion());
        }

        return theme_manager()->asset($path);
    }
}

if (! function_exists('theme_is_preview')) {
    function theme_is_preview(): bool
    {
        return app()->bound('theme.preview_session');
    }
}

if (! function_exists('theme_preview_session')) {
    /**
     * @return array<string, mixed>|null
     */
    function theme_preview_session(): ?array
    {
        if (! app()->bound('theme.preview_session')) {
            return null;
        }

        /** @var array<string, mixed>|null $session */
        $session = app('theme.preview_session');

        return is_array($session) ? $session : null;
    }
}

if (! function_exists('theme_setting')) {
    function theme_setting(string $key, mixed $default = null): mixed
    {
        // In preview mode, return draft settings if available
        $session = theme_preview_session();

        if ($session !== null && isset($session['settings']) && is_array($session['settings'])) {
            if (array_key_exists($key, $session['settings'])) {
                return $session['settings'][$key];
            }
        }

        return theme_manager()->setting($key, $default);
    }
}

if (! function_exists('theme_setting_json')) {
    /**
     * @return array<int|string, mixed>
     */
    function theme_setting_json(string $key, array $default = []): array
    {
        $value = theme_setting($key);

        if (is_array($value)) {
            return $value;
        }

        if (! is_string($value) || trim($value) === '') {
            return $default;
        }

        try {
            $decoded = json_decode($value, true, 512, JSON_THROW_ON_ERROR);
        } catch (Throwable) {
            return $default;
        }

        return is_array($decoded) ? $decoded : $default;
    }
}

if (! function_exists('theme_media_url')) {
    function theme_media_url(?string $path, string $fallback = ''): string
    {
        $resolved = MediaUrl::resolve($path);

        if (is_string($resolved) && $resolved !== '') {
            return $resolved;
        }

        return $fallback;
    }
}

if (! function_exists('theme_site_name')) {
    function theme_site_name(): string
    {
        return (string) app(\App\Services\SettingService::class)->get('general', 'site_name', config('app.name'));
    }
}

if (! function_exists('theme_home_url')) {
    function theme_home_url(): string
    {
        return route('storefront.home', [], false);
    }
}

if (! function_exists('theme_route_url')) {
    /**
     * @param  array<string, mixed>  $parameters
     */
    function theme_route_url(string $name, array $parameters = [], bool $absolute = false): string
    {
        return route($name, $parameters, $absolute);
    }
}

if (! function_exists('theme_url')) {
    function theme_url(string $path = ''): string
    {
        $path = trim($path);

        if ($path === '') {
            return '/';
        }

        if (preg_match('/^(?:[a-z][a-z0-9+.-]*:)?\\/\\//i', $path) === 1) {
            return $path;
        }

        return '/' . ltrim($path, '/');
    }
}

if (! function_exists('theme_locale')) {
    function theme_locale(): string
    {
        return app()->getLocale();
    }
}

if (! function_exists('theme_locale_is')) {
    function theme_locale_is(string $locale): bool
    {
        return theme_locale() === $locale;
    }
}

if (! function_exists('theme_supported_locales')) {
    /**
     * @return array<int, array<string, string>>
     */
    function theme_supported_locales(): array
    {
        return app(LocalizationManager::class)->supportedLocales();
    }
}

if (! function_exists('theme_locale_url')) {
    function theme_locale_url(string $locale): string
    {
        return route('locale.switch', ['locale' => $locale], false);
    }
}

if (! function_exists('theme_text')) {
    /**
     * @param  array<string, mixed>  $replace
     */
    function theme_text(string $key, array $replace = [], ?string $locale = null): string
    {
        $translationKey = str_starts_with($key, 'frontend.')
            ? $key
            : 'frontend.' . ltrim($key, '.');

        return (string) __($translationKey, $replace, $locale);
    }
}

if (! function_exists('theme_has_vite_assets')) {
    function theme_has_vite_assets(): bool
    {
        return file_exists(public_path('hot')) || file_exists(public_path('build/manifest.json'));
    }
}

if (! function_exists('theme_vite_assets')) {
    /**
     * @param  array<int, string>  $entrypoints
     */
    function theme_vite_assets(array $entrypoints = ['resources/scss/frontend.scss']): HtmlString
    {
        if (! theme_has_vite_assets()) {
            return new HtmlString('');
        }

        return app(Vite::class)($entrypoints);
    }
}

if (! function_exists('theme_menu')) {
    /**
     * @return array<int, array<string, mixed>>
     */
    function theme_menu(string $alias, array $default = []): array
    {
        return theme_manager()->menu($alias, $default);
    }
}

if (! function_exists('theme_menu_items')) {
    /**
     * @return array<int, array<string, mixed>>
     */
    function theme_menu_items(string $alias, array $default = []): array
    {
        return theme_menu($alias, $default);
    }
}

if (! function_exists('theme_has_menu')) {
    function theme_has_menu(string $alias, array $default = []): bool
    {
        return theme_menu_items($alias, $default) !== [];
    }
}

if (! function_exists('theme_menu_label')) {
    /**
     * @param  array<string, mixed>  $item
     */
    function theme_menu_label(array $item, ?string $locale = null): string
    {
        $resolvedLocale = $locale ?? theme_locale();
        $labels = $item['labels'] ?? $item['label'] ?? [];

        if (is_string($labels)) {
            return $labels;
        }

        if (! is_array($labels) || $labels === []) {
            return '';
        }

        $fallbackLocale = (string) config('app.fallback_locale', 'en');

        return (string) ($labels[$resolvedLocale] ?? $labels[$fallbackLocale] ?? reset($labels) ?: '');
    }
}

if (! function_exists('theme_menu_url')) {
    /**
     * @param  array<string, mixed>  $item
     */
    function theme_menu_url(array $item, string $default = '#'): string
    {
        return (string) ($item['url'] ?? $default);
    }
}

if (! function_exists('theme_menu_target')) {
    /**
     * @param  array<string, mixed>  $item
     */
    function theme_menu_target(array $item, string $default = '_self'): string
    {
        $target = (string) ($item['target'] ?? $default);

        return in_array($target, ['_self', '_blank'], true) ? $target : $default;
    }
}

if (! function_exists('theme_menu_rel')) {
    /**
     * @param  array<string, mixed>  $item
     */
    function theme_menu_rel(array $item): ?string
    {
        return theme_menu_target($item) === '_blank' ? 'noreferrer noopener' : null;
    }
}

if (! function_exists('theme_menu_icon_class')) {
    /**
     * @param  array<string, mixed>  $item
     */
    function theme_menu_icon_class(array $item): ?string
    {
        $icon = trim((string) ($item['icon'] ?? ''));

        return $icon !== '' ? $icon : null;
    }
}

if (! function_exists('theme_icon')) {
    function theme_icon(?string $icon, string $class = ''): HtmlString
    {
        $icon = trim((string) $icon);

        if ($icon === '') {
            return new HtmlString('');
        }

        $classes = trim($icon . ' ' . $class);

        return new HtmlString('<i aria-hidden="true" class="' . e($classes) . '"></i>');
    }
}

if (! function_exists('theme_menu_icon')) {
    /**
     * @param  array<string, mixed>  $item
     */
    function theme_menu_icon(array $item, string $class = ''): HtmlString
    {
        return theme_icon(theme_menu_icon_class($item), $class);
    }
}

if (! function_exists('theme_money')) {
    function theme_money(int|float|string|null $amount, string $currency = '₫'): string
    {
        if (! is_numeric($amount)) {
            return '0 ' . $currency;
        }

        $value = (float) $amount;
        $locale = theme_locale() === 'vi' ? 'vi_VN' : 'en_US';
        $decimals = fmod($value, 1.0) === 0.0 ? 0 : 2;

        if (class_exists(\NumberFormatter::class)) {
            $formatter = new \NumberFormatter($locale, \NumberFormatter::DECIMAL);
            $formatter->setAttribute(\NumberFormatter::MIN_FRACTION_DIGITS, $decimals);
            $formatter->setAttribute(\NumberFormatter::MAX_FRACTION_DIGITS, $decimals);

            $formatted = $formatter->format($value);

            if ($formatted !== false) {
                return trim($formatted . ' ' . $currency);
            }
        }

        return trim(
            number_format(
                $value,
                $decimals,
                theme_locale() === 'vi' ? ',' : '.',
                theme_locale() === 'vi' ? '.' : ',',
            ) . ' ' . $currency,
        );
    }
}
