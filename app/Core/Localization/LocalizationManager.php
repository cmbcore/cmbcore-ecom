<?php

declare(strict_types=1);

namespace App\Core\Localization;

use App\Services\SettingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use InvalidArgumentException;

class LocalizationManager
{
    public function __construct(
        private readonly SettingService $settingService,
    ) {
    }

    public function resolveLocale(Request $request): string
    {
        $requestedLocale = $request->header('X-Locale')
            ?? $request->query('lang')
            ?? $request->cookie($this->cookieName())
            ?? $this->defaultLocale();

        if (! is_string($requestedLocale) || ! $this->isSupported($requestedLocale)) {
            return $this->defaultLocale();
        }

        return $requestedLocale;
    }

    public function apply(string $locale): string
    {
        $locale = $this->normalizeLocale($locale);

        App::setLocale($locale);

        return $locale;
    }

    /**
     * @return array<int, array<string, string>>
     */
    public function supportedLocales(): array
    {
        $supported = config('localization.supported', []);
        $enabledLocales = $this->enabledLocaleCodes();

        return collect($supported)
            ->filter(static fn (array $locale, string $code): bool => in_array($code, $enabledLocales, true))
            ->map(static function (array $locale, string $code): array {
                return [
                    'code' => $code,
                    'name' => (string) ($locale['name'] ?? $code),
                    'native_name' => (string) ($locale['native_name'] ?? $locale['name'] ?? $code),
                    'icon' => (string) ($locale['icon'] ?? 'fa-solid fa-language'),
                ];
            })
            ->values()
            ->all();
    }

    public function defaultLocale(): string
    {
        $storedLocale = $this->settingService->get('localization', 'default_locale');

        if (is_string($storedLocale) && $this->isSupported($storedLocale)) {
            return $storedLocale;
        }

        $defaultLocale = (string) config('localization.default_locale', config('app.locale', 'vi'));

        return $this->isSupported($defaultLocale)
            ? $defaultLocale
            : (string) config('app.fallback_locale', 'en');
    }

    public function cookieName(): string
    {
        return (string) config('localization.cookie_name', 'cmbcore_locale');
    }

    public function isSupported(string $locale): bool
    {
        return in_array($locale, $this->enabledLocaleCodes(), true);
    }

    /**
     * @return array<string, mixed>
     */
    public function adminPayload(?string $locale = null): array
    {
        $resolvedLocale = $locale && $this->isSupported($locale)
            ? $locale
            : App::currentLocale();

        return [
            'current_locale' => $resolvedLocale,
            'supported_locales' => $this->supportedLocales(),
            'translations' => trans('admin', locale: $resolvedLocale),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function frontendPayload(?string $locale = null): array
    {
        $resolvedLocale = $locale && $this->isSupported($locale)
            ? $locale
            : App::currentLocale();

        return [
            'current_locale' => $resolvedLocale,
            'supported_locales' => $this->supportedLocales(),
            'translations' => trans('frontend', locale: $resolvedLocale),
        ];
    }

    private function normalizeLocale(string $locale): string
    {
        if (! $this->isSupported($locale)) {
            throw new InvalidArgumentException(__('admin.locale.errors.unsupported'));
        }

        return $locale;
    }

    /**
     * @return array<int, string>
     */
    private function enabledLocaleCodes(): array
    {
        $storedLocales = $this->settingService->get('localization', 'supported_locales');
        $supported = array_keys((array) config('localization.supported', []));

        if (is_array($storedLocales) && $storedLocales !== []) {
            return array_values(array_intersect($supported, $storedLocales));
        }

        return $supported;
    }
}
