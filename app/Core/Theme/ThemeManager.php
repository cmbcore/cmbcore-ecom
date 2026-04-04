<?php

declare(strict_types=1);

namespace App\Core\Theme;

use App\Core\Theme\Data\ThemeManifest;
use App\Models\InstalledTheme;
use App\Services\PackageZipInstaller;
use Illuminate\Contracts\View\View as ViewContract;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

class ThemeManager
{
    /** @var array<string, string> */
    private const LEGACY_THEME_ALIASES = [
        'rhysman' => 'cmbcore',
    ];

    /** @var array<string, string> */
    private const LEGACY_THEME_ASSET_URLS = [
        'https://rhysman.vn/wp-content/uploads/2026/01/BANNER-WEB-PC.png' => '/theme-assets/cmbcore/demo/hero-slide-1-desktop.png',
        'https://rhysman.vn/wp-content/smush-webp/2026/01/BANNER-WEB-MOBIE.png.webp' => '/theme-assets/cmbcore/demo/hero-slide-1-mobile.webp',
        'https://rhysman.vn/wp-content/smush-webp/2025/10/BANNER-WEB-Box-thuong.png.webp' => '/theme-assets/cmbcore/demo/hero-slide-2-desktop.webp',
        'https://rhysman.vn/wp-content/smush-webp/2025/10/BANNER-WEB-Box-thuong-M.png.webp' => '/theme-assets/cmbcore/demo/hero-slide-2-mobile.webp',
        'https://rhysman.vn/wp-content/uploads/2025/05/THUMB-NAM-TINH-1.png' => '/theme-assets/cmbcore/demo/quote-card-1.png',
        'https://rhysman.vn/wp-content/uploads/2025/05/cham-soc-toan-dien_09312636a1b9429e9955e161c9429c4c.jpg' => '/theme-assets/cmbcore/demo/quote-card-2.jpg',
        'https://rhysman.vn/wp-content/uploads/2025/05/cham-soc-toan-dien-cho-nam-gioi_7e4977521e4849eca099d725ce266a0d.jpg' => '/theme-assets/cmbcore/demo/quote-card-3.jpg',
        'https://rhysman.vn/wp-content/smush-webp/2025/06/logo-bct.png.webp' => '/theme-assets/cmbcore/demo/footer-badge.webp',
    ];

    private ?Collection $themes = null;

    private bool $themesSynced = false;

    private bool $namespacesRegistered = false;

    public function __construct(
        private readonly Filesystem $files,
        private readonly ThemeViewContext $viewContext,
        private readonly PackageZipInstaller $packageZipInstaller,
    )
    {
    }

    /**
     * @return Collection<int, ThemeManifest>
     */
    public function getAll(): Collection
    {
        if ($this->themes instanceof Collection) {
            return $this->themes;
        }

        $themes = collect();

        foreach (config('themes.scan_paths', []) as $scanPath) {
            if (! is_string($scanPath) || ! $this->files->isDirectory($scanPath)) {
                continue;
            }

            foreach ($this->files->directories($scanPath) as $directory) {
                $manifestPath = $directory . DIRECTORY_SEPARATOR . 'theme.json';

                if (! $this->files->exists($manifestPath)) {
                    continue;
                }

                try {
                    /** @var array<string, mixed> $data */
                    $data = json_decode($this->files->get($manifestPath), true, 512, JSON_THROW_ON_ERROR);
                    $themes->push(ThemeManifest::fromArray($data, $directory));
                } catch (Throwable $exception) {
                    Log::warning('Failed to load theme manifest.', [
                        'manifest' => $manifestPath,
                        'error' => $exception->getMessage(),
                    ]);
                }
            }
        }

        return $this->themes = $themes->values();
    }

    public function getActive(): ThemeManifest
    {
        $themes = $this->getAll();

        if ($themes->isEmpty()) {
            throw new RuntimeException('No themes are available.');
        }

        $previewAlias = $this->previewThemeAlias();

        if ($previewAlias !== null) {
            $previewTheme = $themes->first(
                static fn (ThemeManifest $theme): bool => $theme->getAlias() === $previewAlias,
            );

            if ($previewTheme instanceof ThemeManifest) {
                return $previewTheme;
            }
        }

        $activeAlias = $this->resolveActiveAlias();
        $defaultAlias = (string) config('themes.default', 'default');

        return $themes->first(static fn (ThemeManifest $theme): bool => $theme->getAlias() === $activeAlias)
            ?? $themes->first(static fn (ThemeManifest $theme): bool => $theme->getAlias() === $defaultAlias)
            ?? $themes->first();
    }

    public function find(string $alias): ?ThemeManifest
    {
        return $this->getAll()->first(
            static fn (ThemeManifest $theme): bool => $theme->getAlias() === $alias,
        );
    }

    public function activate(string $alias): void
    {
        $theme = $this->getAll()->first(static fn (ThemeManifest $item): bool => $item->getAlias() === $alias);

        if (! $theme instanceof ThemeManifest) {
            throw new RuntimeException("Theme [{$alias}] was not found.");
        }

        if (! $this->installedThemesTableAvailable()) {
            throw new RuntimeException('The installed_themes table is not available yet.');
        }

        $this->syncInstalledThemes();

        DB::transaction(function () use ($theme): void {
            DB::table('installed_themes')->update(['is_active' => false]);
            DB::table('installed_themes')
                ->where('alias', $theme->getAlias())
                ->update(['is_active' => true, 'updated_at' => now()]);
        });
    }

    public function delete(string $alias): void
    {
        $theme = $this->find($alias);

        if (! $theme instanceof ThemeManifest) {
            throw new RuntimeException("Theme [{$alias}] was not found.");
        }

        if (! $this->installedThemesTableAvailable()) {
            throw new RuntimeException('The installed_themes table is not available yet.');
        }

        $this->syncInstalledThemes();

        $themeRecord = InstalledTheme::query()->where('alias', $alias)->first();

        if ($themeRecord instanceof InstalledTheme && $themeRecord->is_active) {
            throw ValidationException::withMessages([
                'theme' => ['Activate another theme before deleting this one.'],
            ]);
        }

        DB::table('installed_themes')->where('alias', $alias)->delete();
        $this->deleteThemeDirectory($theme);
        $this->flushCache();
    }

    public function setting(string $key, mixed $default = null): mixed
    {
        $previewSettings = $this->previewSettings();

        if (array_key_exists($key, $previewSettings)) {
            return $previewSettings[$key];
        }

        $theme = $this->getActive();
        $storedSettings = $this->storedSettings($theme->getAlias());

        if (array_key_exists($key, $storedSettings)) {
            return $storedSettings[$key];
        }

        $defaults = $this->resolvedDefaultSettings($theme);

        return $defaults[$key] ?? $default;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function menu(string $alias, array $default = []): array
    {
        $theme = $this->getActive();
        $storedMenus = $this->storedMenus($theme->getAlias());
        $previewMenus = $this->previewMenus($theme);
        $defaultMenus = $this->defaultMenus($theme);
        $menuItems = $previewMenus[$alias] ?? $storedMenus[$alias] ?? $defaultMenus[$alias] ?? $default;

        return array_values(array_map(
            fn (array $item): array => $this->resolveMenuItem($item),
            array_filter($menuItems, static fn (mixed $item): bool => is_array($item)),
        ));
    }

    /**
     * @return array<string, mixed>
     */
    public function configuration(string $alias): array
    {
        $theme = $this->find($alias);

        if (! $theme instanceof ThemeManifest) {
            throw new RuntimeException("Theme [{$alias}] was not found.");
        }

        $this->syncInstalledThemes();

        $storedSettings = $this->normalizeThemeSettings(
            $theme,
            Arr::except($this->storedSettings($alias), ['menus']),
        );
        $storedMenus = $this->storedMenus($alias);

        return [
            'theme' => $this->themePayload($theme),
            'settings_schema' => $this->resolvedSettingsSchema($theme),
            'settings' => array_replace(
                $this->resolvedDefaultSettings($theme),
                $storedSettings,
            ),
            'menus' => $this->menuDefinitions($theme, $storedMenus),
        ];
    }

    /**
     * @param  array<string, mixed>  $settings
     * @param  array<int, array<string, mixed>>  $menus
     * @return array<string, mixed>
     */
    public function updateConfiguration(string $alias, array $settings = [], array $menus = []): array
    {
        $theme = $this->find($alias);

        if (! $theme instanceof ThemeManifest) {
            throw new RuntimeException("Theme [{$alias}] was not found.");
        }

        if (! $this->installedThemesTableAvailable()) {
            throw new RuntimeException('The installed_themes table is not available yet.');
        }

        $this->syncInstalledThemes();

        $themeRecord = InstalledTheme::query()->where('alias', $alias)->first();

        if (! $themeRecord instanceof InstalledTheme) {
            throw new RuntimeException("Installed theme [{$alias}] was not found.");
        }

        $currentSettings = is_array($themeRecord->settings) ? $themeRecord->settings : [];

        $themeRecord->forceFill([
            'settings' => array_replace(
                Arr::except($currentSettings, ['menus']),
                $this->normalizeThemeSettings($theme, $settings),
                [
                    'menus' => $this->normalizeThemeMenus($theme, $menus),
                ],
            ),
        ])->save();

        return $this->configuration($alias);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function payloads(): array
    {
        $this->syncInstalledThemes();

        return $this->getAll()
            ->map(fn (ThemeManifest $theme): array => $this->themePayload($theme))
            ->all();
    }

    /**
     * @return ViewContract
     */
    public function view(string $name, array $data = []): ViewContract
    {
        $this->viewContext->replace($data);

        return view($this->viewName($name), $data);
    }

    public function viewName(string $name): string
    {
        $this->registerNamespaces();

        $theme = $this->getActive();

        foreach ($this->viewCandidates($theme) as $candidateAlias) {
            $candidate = 'theme-' . $candidateAlias . '::' . $name;

            if (View::exists($candidate)) {
                return $candidate;
            }
        }

        $frontendFallback = 'frontend.' . str_replace('/', '.', $name);

        if (View::exists($frontendFallback)) {
            return $frontendFallback;
        }

        if (View::exists($name)) {
            return $name;
        }

        throw new RuntimeException("View [{$name}] could not be resolved for the active theme.");
    }

    public function asset(string $path): string
    {
        return asset(trim('themes/' . $this->getActive()->getAlias() . '/assets/' . ltrim($path, '/'), '/'));
    }

    public function supports(string $feature): bool
    {
        return in_array($feature, $this->getActive()->getSupports(), true);
    }

    /**
     * @return array<string, mixed>
     */
    public function installFromArchive(UploadedFile $archive, bool $force = false): array
    {
        $package = $this->packageZipInstaller->unpack($archive, 'theme.json');

        try {
            $manifest = ThemeManifest::fromArray($package['manifest'], $package['package_root']);
            $alias = $manifest->getAlias();
            $targetPath = base_path('themes' . DIRECTORY_SEPARATOR . $alias);

            $existing = $this->find($alias);

            if ($existing instanceof ThemeManifest && ! $force) {
                $isActive = false;

                if ($this->installedThemesTableAvailable()) {
                    $themeRecord = InstalledTheme::query()->where('alias', $alias)->first();
                    $isActive = $themeRecord instanceof InstalledTheme && $themeRecord->is_active;
                }

                throw ValidationException::withMessages([
                    'duplicate' => [json_encode([
                        'alias' => $alias,
                        'name' => $existing->getName(),
                        'version' => $existing->getVersion(),
                        'is_active' => $isActive,
                    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)],
                ]);
            }

            $this->publishPackageDirectory($package['package_root'], $targetPath, base_path('themes'));
            $this->flushCache();
            $this->syncInstalledThemes();

            if (($manifest->getInstall()['activate'] ?? false) === true) {
                $this->activate($alias);
            }

            return $this->configuration($alias);
        } finally {
            $this->packageZipInstaller->cleanup($package['extract_path']);
        }
    }

    public function flushCache(): void
    {
        $this->themes = null;
        $this->namespacesRegistered = false;
        $this->themesSynced = false;
    }

    public function registerNamespaces(): void
    {
        if ($this->namespacesRegistered) {
            return;
        }

        foreach ($this->getAll() as $theme) {
            $viewsPath = $theme->getPath() . DIRECTORY_SEPARATOR . 'views';

            if ($this->files->isDirectory($viewsPath)) {
                View::addNamespace('theme-' . $theme->getAlias(), $viewsPath);
            }
        }

        $this->namespacesRegistered = true;
    }

    public function syncInstalledThemes(): void
    {
        if ($this->themesSynced || ! $this->installedThemesTableAvailable()) {
            return;
        }

        $this->themesSynced = true;

        $availableAliases = $this->getAll()
            ->map(static fn (ThemeManifest $theme): string => $theme->getAlias())
            ->all();

        $this->migrateLegacyThemeAliases($availableAliases);

        $existingThemes = InstalledTheme::query()
            ->get()
            ->keyBy(static fn (InstalledTheme $theme): string => $theme->alias);

        $payloads = $this->getAll()->map(function (ThemeManifest $theme) use ($existingThemes): array {
            /** @var InstalledTheme|null $existingTheme */
            $existingTheme = $existingThemes->get($theme->getAlias());
            $storedSettings = $this->migrateLegacySettingPaths(
                is_array($existingTheme?->settings) ? $existingTheme->settings : [],
            );
            $mergedSettings = array_replace(
                $this->resolvedDefaultSettings($theme),
                $this->normalizeThemeSettings($theme, Arr::except($storedSettings, ['menus'])),
            );

            $menus = $this->normalizeThemeMenus(
                $theme,
                $this->menuDefinitions($theme, $storedSettings['menus'] ?? []),
            );

            return [
                'name' => $theme->getName(),
                'alias' => $theme->getAlias(),
                'version' => $theme->getVersion(),
                'settings' => json_encode(
                    array_replace($mergedSettings, ['menus' => $menus]),
                    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR,
                ),
                'installed_at' => $existingTheme?->installed_at ?? now(),
                'updated_at' => now(),
                'created_at' => $existingTheme?->created_at ?? now(),
            ];
        })->all();

        if ($payloads !== []) {
            InstalledTheme::query()->upsert(
                $payloads,
                ['alias'],
                ['name', 'version', 'settings', 'updated_at'],
            );
        }

        $activeAlias = $this->resolveActiveAlias();
        DB::table('installed_themes')->where('alias', $activeAlias)->update(['is_active' => true]);
    }

    /**
     * @return array<int, string>
     */
    private function viewCandidates(ThemeManifest $theme): array
    {
        $candidates = [$theme->getAlias()];
        $parentAlias = $theme->getParent();

        while (is_string($parentAlias)) {
            $parent = $this->getAll()->first(static fn (ThemeManifest $item): bool => $item->getAlias() === $parentAlias);

            if (! $parent instanceof ThemeManifest) {
                break;
            }

            $candidates[] = $parent->getAlias();
            $parentAlias = $parent->getParent();
        }

        return $candidates;
    }

    private function resolveActiveAlias(): string
    {
        if (! $this->installedThemesTableAvailable()) {
            return (string) config('themes.default', 'default');
        }

        $activeAlias = DB::table('installed_themes')->where('is_active', true)->value('alias');

        return is_string($activeAlias) && $activeAlias !== ''
            ? $this->normalizeStoredAlias($activeAlias)
            : (string) config('themes.default', 'default');
    }

    /**
     * @param  array<int, string>  $availableAliases
     */
    private function migrateLegacyThemeAliases(array $availableAliases): void
    {
        foreach (self::LEGACY_THEME_ALIASES as $legacyAlias => $currentAlias) {
            if (! in_array($currentAlias, $availableAliases, true)) {
                continue;
            }

            $legacyTheme = InstalledTheme::query()->where('alias', $legacyAlias)->first();

            if (! $legacyTheme instanceof InstalledTheme) {
                continue;
            }

            $currentTheme = InstalledTheme::query()->where('alias', $currentAlias)->first();

            if ($currentTheme instanceof InstalledTheme) {
                if ($legacyTheme->is_active && ! $currentTheme->is_active) {
                    $currentTheme->forceFill(['is_active' => true])->save();
                }

                $legacyTheme->delete();

                continue;
            }

            $legacyTheme->forceFill([
                'alias' => $currentAlias,
                'updated_at' => now(),
            ])->save();
        }
    }

    private function normalizeStoredAlias(string $alias): string
    {
        return self::LEGACY_THEME_ALIASES[$alias] ?? $alias;
    }

    private function migrateLegacySettingPaths(mixed $value): mixed
    {
        if (is_array($value)) {
            return array_map(fn (mixed $item): mixed => $this->migrateLegacySettingPaths($item), $value);
        }

        if (! is_string($value)) {
            return $value;
        }

        return $this->migrateLegacyAssetUrl(
            str_replace('/theme-assets/rhysman/', '/theme-assets/cmbcore/', $value),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function storedSettings(string $alias): array
    {
        if (! $this->installedThemesTableAvailable()) {
            return [];
        }

        $theme = InstalledTheme::query()->where('alias', $alias)->first();

        if (! $theme instanceof InstalledTheme || ! is_array($theme->settings)) {
            return [];
        }

        return $theme->settings;
    }

    private function installedThemesTableAvailable(): bool
    {
        try {
            return Schema::hasTable('installed_themes');
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * @param  array<string, mixed>  $storedMenus
     * @return array<int, array<string, mixed>>
     */
    private function menuDefinitions(ThemeManifest $theme, array $storedMenus): array
    {
        return array_values(array_map(function (array $menu) use ($storedMenus): array {
            $alias = (string) ($menu['alias'] ?? '');

            return array_replace($menu, [
                'items' => $storedMenus[$alias] ?? array_values($menu['items'] ?? []),
            ]);
        }, $theme->getMenus()));
    }

    /**
     * @return array<string, array<int, array<string, mixed>>>
     */
    private function defaultMenus(ThemeManifest $theme): array
    {
        return collect($theme->getMenus())
            ->mapWithKeys(static function (array $menu): array {
                return [
                    (string) ($menu['alias'] ?? '') => array_values($menu['items'] ?? []),
                ];
            })
            ->all();
    }

    /**
     * @return array<string, array<int, array<string, mixed>>>
     */
    private function storedMenus(string $alias): array
    {
        $storedSettings = $this->storedSettings($alias);
        $menus = $storedSettings['menus'] ?? [];

        return is_array($menus) ? $menus : [];
    }

    /**
     * @return array<string, mixed>
     */
    private function themePayload(ThemeManifest $theme): array
    {
        $themeRecord = $this->installedThemesTableAvailable()
            ? InstalledTheme::query()->where('alias', $theme->getAlias())->first()
            : null;

        return [
            'name' => $theme->getName(),
            'alias' => $theme->getAlias(),
            'version' => $theme->getVersion(),
            'description' => $theme->getDescription(),
            'author' => $theme->getAuthor(),
            'supports' => $theme->getSupports(),
            'screenshot' => $theme->getScreenshot(),
            'is_active' => $themeRecord?->is_active ?? ($theme->getAlias() === $this->resolveActiveAlias()),
            'menus' => $theme->getMenus(),
        ];
    }

    /**
     * @param  array<string, mixed>  $settings
     * @return array<string, mixed>
     */
    private function normalizeThemeSettings(ThemeManifest $theme, array $settings): array
    {
        $allowedSettings = array_keys($this->resolvedDefaultSettings($theme));
        $normalizedSettings = [];

        foreach ($this->resolvedSettingsSchema($theme) as $group) {
            foreach ($group['fields'] ?? [] as $field) {
                $key = (string) ($field['key'] ?? '');

                if ($key === '' || ! in_array($key, $allowedSettings, true) || ! array_key_exists($key, $settings)) {
                    continue;
                }

                $normalizedSettings[$key] = $this->normalizeSettingValue(
                    $field,
                    $settings[$key],
                    $field['default'] ?? null,
                );
            }
        }

        return $normalizedSettings;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function resolvedSettingsSchema(ThemeManifest $theme): array
    {
        $schema = $theme->getSettingsSchema();

        if ($theme->getAlias() !== 'cmbcore') {
            return $schema;
        }

        return array_map(function (array $group): array {
            if (($group['group'] ?? null) !== 'home') {
                return $group;
            }

            $fields = array_values(array_filter(
                (array) ($group['fields'] ?? []),
                static fn (array $field): bool => ($field['key'] ?? null) !== 'home_category_slugs',
            ));

            array_unshift($fields, [
                'key' => 'home_product_sections',
                'label' => 'Block sản phẩm trang chủ',
                'type' => 'repeater',
                'fields' => [
                    [
                        'key' => 'title',
                        'label' => 'Tiêu đề block',
                        'type' => 'text',
                        'default' => '',
                        'span' => 10,
                    ],
                    [
                        'key' => 'source_type',
                        'label' => 'Nguồn dữ liệu',
                        'type' => 'select',
                        'default' => 'category',
                        'span' => 6,
                        'options' => [
                            ['label' => 'Sản phẩm nổi bật', 'value' => 'featured'],
                            ['label' => 'Sản phẩm mới', 'value' => 'latest'],
                            ['label' => 'Theo danh mục', 'value' => 'category'],
                        ],
                    ],
                    [
                        'key' => 'category_slug',
                        'label' => 'Danh mục',
                        'type' => 'category-select',
                        'default' => '',
                        'span' => 8,
                    ],
                    [
                        'key' => 'limit',
                        'label' => 'Số sản phẩm',
                        'type' => 'number',
                        'default' => 8,
                        'min' => 4,
                        'max' => 12,
                        'span' => 6,
                    ],
                ],
                'default' => [
                    [
                        'title' => 'Bán chạy nhất',
                        'source_type' => 'featured',
                        'category_slug' => '',
                        'limit' => 8,
                    ],
                    [
                        'title' => 'Sản phẩm mới',
                        'source_type' => 'latest',
                        'category_slug' => '',
                        'limit' => 8,
                    ],
                    [
                        'title' => '',
                        'source_type' => 'category',
                        'category_slug' => 'bo-qua-tang-cho-nam',
                        'limit' => 8,
                    ],
                    [
                        'title' => '',
                        'source_type' => 'category',
                        'category_slug' => 'cham-soc-co-the',
                        'limit' => 8,
                    ],
                    [
                        'title' => '',
                        'source_type' => 'category',
                        'category_slug' => 'san-pham-khu-mui',
                        'limit' => 8,
                    ],
                    [
                        'title' => '',
                        'source_type' => 'category',
                        'category_slug' => 'cham-soc-toc',
                        'limit' => 8,
                    ],
                ],
            ]);

            $group['fields'] = $fields;

            return $group;
        }, $schema);
    }

    /**
     * @return array<string, mixed>
     */
    private function resolvedDefaultSettings(ThemeManifest $theme): array
    {
        $defaults = $theme->getDefaultSettings();

        if ($theme->getAlias() !== 'cmbcore') {
            return $defaults;
        }

        unset($defaults['home_category_slugs']);
        $defaults['home_product_sections'] = [
            [
                'title' => 'Bán chạy nhất',
                'source_type' => 'featured',
                'category_slug' => '',
                'limit' => 8,
            ],
            [
                'title' => 'Sản phẩm mới',
                'source_type' => 'latest',
                'category_slug' => '',
                'limit' => 8,
            ],
            [
                'title' => '',
                'source_type' => 'category',
                'category_slug' => 'bo-qua-tang-cho-nam',
                'limit' => 8,
            ],
            [
                'title' => '',
                'source_type' => 'category',
                'category_slug' => 'cham-soc-co-the',
                'limit' => 8,
            ],
            [
                'title' => '',
                'source_type' => 'category',
                'category_slug' => 'san-pham-khu-mui',
                'limit' => 8,
            ],
            [
                'title' => '',
                'source_type' => 'category',
                'category_slug' => 'cham-soc-toc',
                'limit' => 8,
            ],
        ];

        return $defaults;
    }

    /**
     * @param  array<int, array<string, mixed>>  $menus
     * @return array<string, array<int, array<string, mixed>>>
     */
    private function normalizeThemeMenus(ThemeManifest $theme, array $menus): array
    {
        $supportedLocales = array_keys((array) config('localization.supported', []));
        $menuDefinitions = collect($theme->getMenus())
            ->keyBy(static fn (array $menu): string => (string) ($menu['alias'] ?? ''));

        $normalizedMenus = [];

        foreach ($menus as $menu) {
            $alias = (string) ($menu['alias'] ?? '');

            if ($alias === '' || ! $menuDefinitions->has($alias)) {
                continue;
            }

            $normalizedMenus[$alias] = [];

            foreach ((array) ($menu['items'] ?? []) as $item) {
                if (! is_array($item)) {
                    continue;
                }

                $labels = [];

                foreach ($supportedLocales as $locale) {
                    $value = trim((string) data_get($item, "label.{$locale}", ''));

                    if ($value !== '') {
                        $labels[$locale] = $value;
                    }
                }

                if ($labels === []) {
                    continue;
                }

                $url = trim((string) ($item['url'] ?? ''));

                if ($url === '') {
                    continue;
                }

                $normalizedMenus[$alias][] = [
                    'label' => $labels,
                    'url' => $url,
                    'icon' => $this->normalizeIcon((string) ($item['icon'] ?? '')),
                    'target' => in_array($item['target'] ?? '_self', ['_self', '_blank'], true)
                        ? (string) $item['target']
                        : '_self',
                ];
            }
        }

        return $normalizedMenus;
    }

    /**
     * @return array<string, mixed>
     */
    private function resolveMenuItem(array $item): array
    {
        return [
            'label' => $this->resolveLocalizedValue($item['label'] ?? ''),
            'labels' => is_array($item['label'] ?? null) ? $item['label'] : [app()->getLocale() => (string) ($item['label'] ?? '')],
            'url' => (string) ($item['url'] ?? '#'),
            'icon' => $this->normalizeIcon((string) ($item['icon'] ?? '')),
            'target' => in_array($item['target'] ?? '_self', ['_self', '_blank'], true)
                ? (string) $item['target']
                : '_self',
        ];
    }

    private function resolveLocalizedValue(mixed $value): string
    {
        if (is_string($value)) {
            return $value;
        }

        if (! is_array($value) || $value === []) {
            return '';
        }

        $locale = app()->getLocale();
        $fallbackLocale = (string) config('app.fallback_locale', 'en');

        return (string) ($value[$locale] ?? $value[$fallbackLocale] ?? reset($value) ?: '');
    }

    /**
     * @param  array<string, mixed>|string  $field
     */
    private function normalizeSettingValue(mixed $field, mixed $value, mixed $default = null): mixed
    {
        $type = is_array($field) ? (string) ($field['type'] ?? 'text') : (string) $field;

        return match ($type) {
            'number' => is_numeric($value) ? $value + 0 : $default,
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE) ?? $default,
            'select' => is_scalar($value) ? (string) $value : $default,
            'tags' => $this->normalizeTagSettingValue($value, $default),
            'image' => $this->normalizeImageSettingValue($value, $default),
            'object' => $this->normalizeObjectSettingValue(is_array($field) ? $field : [], $value, $default),
            'repeater' => $this->normalizeRepeaterSettingValue(is_array($field) ? $field : [], $value, $default),
            default => is_scalar($value) ? (string) $value : $default,
        };
    }

    /**
     * @return array<int, string>
     */
    private function normalizeTagSettingValue(mixed $value, mixed $default = null): array
    {
        if (is_string($value)) {
            $decoded = json_decode($value, true);

            if (is_array($decoded)) {
                $value = $decoded;
            } else {
                $value = array_map('trim', explode(',', $value));
            }
        }

        if (! is_array($value)) {
            return is_array($default) ? $default : [];
        }

        return collect($value)
            ->filter(static fn (mixed $item): bool => is_scalar($item) && trim((string) $item) !== '')
            ->map(static fn (mixed $item): string => trim((string) $item))
            ->values()
            ->all();
    }

    private function normalizeImageSettingValue(mixed $value, mixed $default = null): ?string
    {
        if (is_string($value)) {
            $value = trim($value);

            return $value !== ''
                ? $this->migrateLegacyAssetUrl(
                    str_replace('/theme-assets/rhysman/', '/theme-assets/cmbcore/', $value),
                )
                : (is_string($default) ? $default : null);
        }

        return is_string($default) ? $default : null;
    }

    /**
     * @param  array<string, mixed>  $field
     * @return array<string, mixed>
     */
    private function normalizeObjectSettingValue(array $field, mixed $value, mixed $default = null): array
    {
        if (is_string($value) && trim($value) !== '') {
            $decoded = json_decode($value, true);
            $value = is_array($decoded) ? $decoded : [];
        }

        $values = is_array($value) ? $value : [];
        $defaults = is_array($default) ? $default : [];
        $normalized = [];

        foreach ((array) ($field['fields'] ?? []) as $childField) {
            $key = (string) ($childField['key'] ?? '');

            if ($key === '') {
                continue;
            }

            $normalized[$key] = $this->normalizeSettingValue(
                $childField,
                $values[$key] ?? ($childField['default'] ?? null),
                $defaults[$key] ?? ($childField['default'] ?? null),
            );
        }

        return $normalized;
    }

    /**
     * @param  array<string, mixed>  $field
     * @return array<int, array<string, mixed>>
     */
    private function normalizeRepeaterSettingValue(array $field, mixed $value, mixed $default = null): array
    {
        if (is_string($value) && trim($value) !== '') {
            $decoded = json_decode($value, true);
            $value = is_array($decoded) ? $decoded : [];
        }

        $items = is_array($value) ? $value : (is_array($default) ? $default : []);
        $normalized = [];

        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }

            $row = [];

            foreach ((array) ($field['fields'] ?? []) as $childField) {
                $key = (string) ($childField['key'] ?? '');

                if ($key === '') {
                    continue;
                }

                $row[$key] = $this->normalizeSettingValue(
                    $childField,
                    $item[$key] ?? ($childField['default'] ?? null),
                    $childField['default'] ?? null,
                );
            }

            $normalized[] = $row;
        }

        return $normalized;
    }

    private function normalizeIcon(string $icon): ?string
    {
        $icon = trim($icon);

        return $icon !== '' ? $icon : null;
    }

    private function migrateLegacyAssetUrl(string $value): string
    {
        return self::LEGACY_THEME_ASSET_URLS[$value] ?? $value;
    }

    private function publishPackageDirectory(string $sourcePath, string $targetPath, string $baseDirectory): void
    {
        $normalizedBase = str_replace('\\', '/', realpath($baseDirectory) ?: $baseDirectory);
        $normalizedTarget = str_replace('\\', '/', $targetPath);

        if (! str_starts_with($normalizedTarget, rtrim($normalizedBase, '/') . '/')) {
            throw new RuntimeException('Target path is outside the allowed theme directory.');
        }

        if ($this->files->isDirectory($targetPath)) {
            $this->files->deleteDirectory($targetPath);
        }

        $this->files->ensureDirectoryExists(dirname($targetPath));

        if (! $this->files->copyDirectory($sourcePath, $targetPath)) {
            throw new RuntimeException('Unable to publish the theme package.');
        }
    }

    private function deleteThemeDirectory(ThemeManifest $theme): void
    {
        $themePath = str_replace('\\', '/', realpath($theme->getPath()) ?: $theme->getPath());
        $allowedBases = collect(config('themes.scan_paths', []))
            ->filter(static fn (mixed $path): bool => is_string($path) && trim($path) !== '')
            ->map(static fn (string $path): string => str_replace('\\', '/', realpath($path) ?: $path))
            ->values();

        $isAllowed = $allowedBases->contains(
            static fn (string $base): bool => str_starts_with($themePath, rtrim($base, '/') . '/'),
        );

        if (! $isAllowed) {
            throw new RuntimeException('Theme path is outside the allowed theme directories.');
        }

        if ($this->files->isDirectory($theme->getPath())) {
            $this->files->deleteDirectory($theme->getPath());
        }
    }

    /**
     * @return array<string, mixed>|null
     */
    private function previewSession(): ?array
    {
        if (! app()->bound('theme.preview_session')) {
            return null;
        }

        $session = app('theme.preview_session');

        return is_array($session) ? $session : null;
    }

    private function previewThemeAlias(): ?string
    {
        $session = $this->previewSession();
        $alias = is_array($session) ? ($session['alias'] ?? null) : null;

        return is_string($alias) && $alias !== ''
            ? $this->normalizeStoredAlias($alias)
            : null;
    }

    /**
     * @return array<string, mixed>
     */
    private function previewSettings(): array
    {
        $session = $this->previewSession();
        $settings = is_array($session) ? ($session['settings'] ?? null) : null;

        return is_array($settings) ? $settings : [];
    }

    /**
     * @return array<string, array<int, array<string, mixed>>>
     */
    private function previewMenus(ThemeManifest $theme): array
    {
        $session = $this->previewSession();
        $menus = is_array($session) ? ($session['menus'] ?? null) : null;

        if (! is_array($menus)) {
            return [];
        }

        return $this->normalizeThemeMenus($theme, $menus);
    }
}
