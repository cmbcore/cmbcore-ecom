<?php

declare(strict_types=1);

namespace App\Core\Plugin;

use App\Core\Plugin\Contracts\PluginInterface;
use App\Core\Plugin\Data\PluginManifest;
use App\Core\Module\ModuleManager;
use App\Models\InstalledPlugin;
use App\Services\PackageZipInstaller;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

class PluginManager
{
    private ?Collection $plugins = null;

    private bool $synced = false;

    /**
     * @var array<string, bool>
     */
    private array $booted = [];

    public function __construct(
        private readonly Filesystem $files,
        private readonly HookManager $hookManager,
        private readonly ModuleManager $moduleManager,
        private readonly PackageZipInstaller $packageZipInstaller,
    ) {
    }

    /**
     * @return Collection<int, PluginManifest>
     */
    public function getAll(): Collection
    {
        if ($this->plugins instanceof Collection) {
            return $this->plugins;
        }

        $plugins = collect();

        foreach (config('plugins.scan_paths', []) as $scanPath) {
            if (! is_string($scanPath) || ! $this->files->isDirectory($scanPath)) {
                continue;
            }

            foreach ($this->files->directories($scanPath) as $directory) {
                $manifestPath = $directory . DIRECTORY_SEPARATOR . 'plugin.json';

                if (! $this->files->exists($manifestPath)) {
                    continue;
                }

                try {
                    /** @var array<string, mixed> $data */
                    $data = json_decode($this->files->get($manifestPath), true, 512, JSON_THROW_ON_ERROR);
                    $plugins->push(PluginManifest::fromArray($data, $directory));
                } catch (Throwable $exception) {
                    Log::warning('Failed to load plugin manifest.', [
                        'manifest' => $manifestPath,
                        'error' => $exception->getMessage(),
                    ]);
                }
            }
        }

        return $this->plugins = $plugins->values();
    }

    /**
     * @return Collection<int, PluginManifest>
     */
    public function getActive(): Collection
    {
        if (! $this->installedPluginsTableAvailable()) {
            return collect();
        }

        $this->syncInstalledPlugins();

        $activeAliases = DB::table('installed_plugins')
            ->where('is_active', true)
            ->pluck('alias')
            ->all();

        return $this->getAll()
            ->filter(static fn (PluginManifest $plugin): bool => in_array($plugin->getAlias(), $activeAliases, true))
            ->values();
    }

    public function bootActivePlugins(): void
    {
        foreach ($this->getActive() as $plugin) {
            $this->bootPlugin($plugin);
        }
    }

    public function find(string $alias): ?PluginManifest
    {
        return $this->getAll()->first(
            static fn (PluginManifest $plugin): bool => $plugin->getAlias() === $alias,
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function payloads(): array
    {
        $this->syncInstalledPlugins();

        return $this->getAll()
            ->map(fn (PluginManifest $plugin): array => $this->pluginPayload($plugin))
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    public function configuration(string $alias): array
    {
        $plugin = $this->find($alias);

        if (! $plugin instanceof PluginManifest) {
            throw new RuntimeException("Plugin [{$alias}] was not found.");
        }

        $this->syncInstalledPlugins();

        return [
            'plugin' => $this->pluginPayload($plugin),
            'settings_schema' => $plugin->getSettings(),
            'settings' => array_replace(
                $this->defaultSettings($plugin),
                $this->storedSettings($alias),
            ),
        ];
    }

    public function enable(string $alias): array
    {
        $plugin = $this->find($alias);

        if (! $plugin instanceof PluginManifest) {
            throw new RuntimeException("Plugin [{$alias}] was not found.");
        }

        if (! $this->requirementsSatisfied($plugin, logFailures: false)) {
            throw new RuntimeException("Plugin [{$alias}] does not satisfy current requirements.");
        }

        if (! $this->installedPluginsTableAvailable()) {
            throw new RuntimeException('The installed_plugins table is not available yet.');
        }

        $this->syncInstalledPlugins();

        DB::transaction(function () use ($plugin): void {
            $instance = $this->instantiatePlugin($plugin);

            DB::table('installed_plugins')
                ->where('alias', $plugin->getAlias())
                ->update([
                    'is_active' => true,
                    'updated_at' => now(),
                ]);

            $instance->activate();
        });

        unset($this->booted[$plugin->getAlias()]);
        $this->bootPlugin($plugin);

        return $this->configuration($alias);
    }

    public function disable(string $alias): array
    {
        $plugin = $this->find($alias);

        if (! $plugin instanceof PluginManifest) {
            throw new RuntimeException("Plugin [{$alias}] was not found.");
        }

        if (! $this->installedPluginsTableAvailable()) {
            throw new RuntimeException('The installed_plugins table is not available yet.');
        }

        $this->syncInstalledPlugins();

        DB::transaction(function () use ($plugin): void {
            $instance = $this->instantiatePlugin($plugin);

            DB::table('installed_plugins')
                ->where('alias', $plugin->getAlias())
                ->update([
                    'is_active' => false,
                    'updated_at' => now(),
                ]);

            $instance->deactivate();
        });

        $this->hookManager->forgetScope($this->scope($plugin));
        unset($this->booted[$plugin->getAlias()]);

        return $this->configuration($alias);
    }

    public function delete(string $alias): void
    {
        $plugin = $this->find($alias);

        if (! $plugin instanceof PluginManifest) {
            throw new RuntimeException("Plugin [{$alias}] was not found.");
        }

        if (! $this->installedPluginsTableAvailable()) {
            throw new RuntimeException('The installed_plugins table is not available yet.');
        }

        $this->syncInstalledPlugins();

        $pluginRecord = InstalledPlugin::query()->where('alias', $alias)->first();

        if ($pluginRecord instanceof InstalledPlugin && $pluginRecord->is_active) {
            throw ValidationException::withMessages([
                'plugin' => ['Deactivate the plugin before deleting it.'],
            ]);
        }

        DB::table('installed_plugins')->where('alias', $alias)->delete();
        $this->hookManager->forgetScope($this->scope($plugin));
        unset($this->booted[$alias]);
        $this->deletePluginDirectory($plugin);
        $this->flushCache();
    }

    /**
     * @param  array<string, mixed>  $settings
     * @return array<string, mixed>
     */
    public function updateConfiguration(string $alias, array $settings = []): array
    {
        $plugin = $this->find($alias);

        if (! $plugin instanceof PluginManifest) {
            throw new RuntimeException("Plugin [{$alias}] was not found.");
        }

        if (! $this->installedPluginsTableAvailable()) {
            throw new RuntimeException('The installed_plugins table is not available yet.');
        }

        $this->syncInstalledPlugins();

        $pluginRecord = InstalledPlugin::query()->where('alias', $alias)->first();

        if (! $pluginRecord instanceof InstalledPlugin) {
            throw new RuntimeException("Installed plugin [{$alias}] was not found.");
        }

        $pluginRecord->forceFill([
            'settings' => array_replace(
                $this->defaultSettings($plugin),
                $this->storedSettings($alias),
                $this->normalizeSettings($plugin, $settings),
            ),
        ])->save();

        return $this->configuration($alias);
    }

    /**
     * @return array<string, string>
     */
    public function getAdminPages(): array
    {
        return $this->getActive()
            ->filter(fn (PluginManifest $plugin): bool => $this->requirementsSatisfied($plugin, logFailures: false))
            ->reduce(
                static function (array $pages, PluginManifest $plugin): array {
                    return array_merge($pages, $plugin->getAdminPages());
                },
                [],
            );
    }

    /**
     * @return array<string, mixed>
     */
    public function installFromArchive(UploadedFile $archive, bool $force = false): array
    {
        $package = $this->packageZipInstaller->unpack($archive, 'plugin.json');

        try {
            $rawManifest = $package['manifest'];
            $alias = trim((string) ($rawManifest['alias'] ?? ''));

            if ($alias === '') {
                throw new RuntimeException('Plugin manifest is missing [alias].');
            }

            $existing = $this->find($alias);

            if ($existing instanceof PluginManifest) {
                $isActive = false;

                if ($this->installedPluginsTableAvailable()) {
                    $pluginRecord = InstalledPlugin::query()->where('alias', $alias)->first();
                    $isActive = $pluginRecord instanceof InstalledPlugin && $pluginRecord->is_active;
                }

                if ($isActive) {
                    throw ValidationException::withMessages([
                        'plugin' => ['Hãy tắt plugin trước khi cài đè phiên bản mới.'],
                    ]);
                }

                if (! $force) {
                    throw ValidationException::withMessages([
                        'duplicate' => [json_encode([
                            'alias' => $alias,
                            'name' => $existing->getName(),
                            'version' => $existing->getVersion(),
                            'is_active' => false,
                        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)],
                    ]);
                }
            }

            $directoryName = $this->installDirectoryName($rawManifest, $package['package_root'], $package['extract_path']);
            $targetPath = base_path('plugins' . DIRECTORY_SEPARATOR . $directoryName);

            $this->publishPackageDirectory($package['package_root'], $targetPath, base_path('plugins'));
            $this->flushCache();

            $manifest = PluginManifest::fromArray($rawManifest, $targetPath);

            $this->syncInstalledPlugins();
            $this->runInstallBootstrap($manifest);

            if (($manifest->getInstall()['auto_enable'] ?? false) === true) {
                return $this->enable($manifest->getAlias());
            }

            return $this->configuration($manifest->getAlias());
        } finally {
            $this->packageZipInstaller->cleanup($package['extract_path']);
        }
    }

    public function flushCache(): void
    {
        $this->plugins = null;
        $this->booted = [];
        $this->synced = false;
    }

    public function syncInstalledPlugins(): void
    {
        if ($this->synced || ! $this->installedPluginsTableAvailable()) {
            return;
        }

        $this->synced = true;

        $existingPlugins = InstalledPlugin::query()
            ->get()
            ->keyBy(static fn (InstalledPlugin $plugin): string => $plugin->alias);

        $payloads = $this->getAll()->map(function (PluginManifest $plugin) use ($existingPlugins): array {
            /** @var InstalledPlugin|null $existingPlugin */
            $existingPlugin = $existingPlugins->get($plugin->getAlias());
            $mergedSettings = array_replace(
                $this->defaultSettings($plugin),
                is_array($existingPlugin?->settings) ? Arr::only($existingPlugin->settings, array_keys($this->defaultSettings($plugin))) : [],
            );

            return [
                'name' => $plugin->getName(),
                'alias' => $plugin->getAlias(),
                'version' => $plugin->getVersion(),
                'settings' => json_encode(
                    $mergedSettings,
                    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR,
                ),
                'installed_at' => $existingPlugin?->installed_at ?? now(),
                'is_active' => $existingPlugin?->is_active ?? false,
                'updated_at' => now(),
                'created_at' => $existingPlugin?->created_at ?? now(),
            ];
        })->all();

        if ($payloads !== []) {
            InstalledPlugin::query()->upsert(
                $payloads,
                ['alias'],
                ['name', 'version', 'settings', 'updated_at'],
            );
        }
    }

    private function requirementsSatisfied(PluginManifest $plugin, bool $logFailures = true): bool
    {
        $requires = $plugin->getRequires();
        $requiredCore = $requires['core'] ?? null;

        if (is_string($requiredCore) && ! $this->versionMatches((string) config('modules.core_version', '1.0.0'), $requiredCore)) {
            if ($logFailures) {
                Log::warning('Plugin core version requirement is not satisfied.', [
                    'plugin' => $plugin->getAlias(),
                    'required' => $requiredCore,
                ]);
            }

            return false;
        }

        foreach ($requires['modules'] ?? [] as $requiredModule) {
            if (! is_string($requiredModule) || ! $this->moduleManager->isEnabled($requiredModule)) {
                if ($logFailures) {
                    Log::warning('Plugin module dependency is not satisfied.', [
                        'plugin' => $plugin->getAlias(),
                        'module' => $requiredModule,
                    ]);
                }

                return false;
            }
        }

        return true;
    }

    private function versionMatches(string $currentVersion, string $constraint): bool
    {
        $operators = ['>=', '<=', '>', '<', '='];

        foreach ($operators as $operator) {
            if (str_starts_with($constraint, $operator)) {
                return version_compare($currentVersion, substr($constraint, strlen($operator)), $operator);
            }
        }

        return version_compare($currentVersion, $constraint, '>=');
    }

    private function installedPluginsTableAvailable(): bool
    {
        try {
            return Schema::hasTable('installed_plugins');
        } catch (Throwable) {
            return false;
        }
    }

    private function bootPlugin(PluginManifest $plugin): void
    {
        if (isset($this->booted[$plugin->getAlias()])) {
            return;
        }

        if (! $this->requirementsSatisfied($plugin)) {
            return;
        }

        try {
            $instance = $this->instantiatePlugin($plugin);
            $scope = $this->scope($plugin);
            $hooks = new ScopedHookManager($this->hookManager, $scope);

            $this->hookManager->forgetScope($scope);
            $this->registerManifestAdminMenu($plugin, $hooks);
            $instance->boot($hooks);

            $this->booted[$plugin->getAlias()] = true;
        } catch (Throwable $exception) {
            Log::warning('Failed to boot plugin.', [
                'plugin' => $plugin->getAlias(),
                'error' => $exception->getMessage(),
            ]);
        }
    }

    private function instantiatePlugin(PluginManifest $plugin): PluginInterface
    {
        if ($this->files->exists($plugin->getMainFile())) {
            require_once $plugin->getMainFile();
        }

        $pluginClass = $plugin->getMainClass();

        if (! class_exists($pluginClass)) {
            throw new RuntimeException("Plugin main class [{$pluginClass}] was not found.");
        }

        $instance = app($pluginClass);

        if (! $instance instanceof PluginInterface) {
            throw new RuntimeException("Plugin [{$plugin->getAlias()}] must implement PluginInterface.");
        }

        return $instance;
    }

    private function registerManifestAdminMenu(PluginManifest $plugin, HookManager $hooks): void
    {
        if ($plugin->getAdminMenu() === []) {
            return;
        }

        $hooks->filter('admin.menu', function (array $menus) use ($plugin): array {
            $pluginMenus = array_map(
                fn (array $menuItem): array => $this->translateMenuItem($menuItem),
                $plugin->getAdminMenu(),
            );

            return array_merge($menus, $pluginMenus);
        });
    }

    /**
     * @return array<string, mixed>
     */
    private function pluginPayload(PluginManifest $plugin): array
    {
        $pluginRecord = $this->installedPluginsTableAvailable()
            ? InstalledPlugin::query()->where('alias', $plugin->getAlias())->first()
            : null;

        return [
            'name' => $plugin->getName(),
            'alias' => $plugin->getAlias(),
            'version' => $plugin->getVersion(),
            'description' => $plugin->getDescription(),
            'author' => $plugin->getAuthor(),
            'url' => $plugin->getUrl(),
            'requires' => $plugin->getRequires(),
            'hooks' => [
                'listens' => $plugin->getListens(),
            ],
            'settings_schema' => $plugin->getSettings(),
            'settings' => array_replace(
                $this->defaultSettings($plugin),
                $this->storedSettings($plugin->getAlias()),
            ),
            'admin' => [
                'menu' => $plugin->getAdminMenu(),
                'pages' => $plugin->getAdminPages(),
            ],
            'is_active' => $pluginRecord?->is_active ?? false,
            'requirements_satisfied' => $this->requirementsSatisfied($plugin, logFailures: false),
            'path' => $plugin->getPath(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function storedSettings(string $alias): array
    {
        if (! $this->installedPluginsTableAvailable()) {
            return [];
        }

        $plugin = InstalledPlugin::query()->where('alias', $alias)->first();

        if (! $plugin instanceof InstalledPlugin || ! is_array($plugin->settings)) {
            return [];
        }

        return $plugin->settings;
    }

    /**
     * @return array<string, mixed>
     */
    private function defaultSettings(PluginManifest $plugin): array
    {
        $defaults = [];

        foreach ($plugin->getSettings() as $group) {
            foreach ((array) ($group['fields'] ?? []) as $field) {
                if (isset($field['key']) && array_key_exists('default', $field)) {
                    $defaults[(string) $field['key']] = $field['default'];
                }
            }
        }

        return $defaults;
    }

    /**
     * @param  array<string, mixed>  $settings
     * @return array<string, mixed>
     */
    private function normalizeSettings(PluginManifest $plugin, array $settings): array
    {
        $allowedSettings = array_keys($this->defaultSettings($plugin));
        $normalizedSettings = [];

        foreach ($plugin->getSettings() as $group) {
            foreach ((array) ($group['fields'] ?? []) as $field) {
                $key = (string) ($field['key'] ?? '');

                if ($key === '' || ! in_array($key, $allowedSettings, true) || ! array_key_exists($key, $settings)) {
                    continue;
                }

                $normalizedSettings[$key] = $this->normalizeSettingValue(
                    (string) ($field['type'] ?? 'text'),
                    $settings[$key],
                    $field['default'] ?? null,
                );
            }
        }

        return $normalizedSettings;
    }

    private function normalizeSettingValue(string $type, mixed $value, mixed $default = null): mixed
    {
        return match ($type) {
            'number' => is_numeric($value) ? $value + 0 : $default,
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE) ?? $default,
            'select' => is_scalar($value) ? (string) $value : $default,
            default => is_scalar($value) ? (string) $value : $default,
        };
    }

    private function scope(PluginManifest $plugin): string
    {
        return 'plugin:' . $plugin->getAlias();
    }

    /**
     * @param  array<string, mixed>  $menuItem
     * @return array<string, mixed>
     */
    private function translateMenuItem(array $menuItem): array
    {
        if (isset($menuItem['translation_key']) && is_string($menuItem['translation_key'])) {
            $menuItem['label'] = __($menuItem['translation_key']);
        }

        if (isset($menuItem['children']) && is_array($menuItem['children'])) {
            $menuItem['children'] = array_map(
                fn (array $child): array => $this->translateMenuItem($child),
                $menuItem['children'],
            );
        }

        return $menuItem;
    }

    /**
     * @param  array<string, mixed>  $manifest
     */
    private function installDirectoryName(array $manifest, string $packageRoot, string $extractRoot): string
    {
        $configured = trim((string) ($manifest['directory'] ?? ''));

        if ($configured !== '') {
            return $configured;
        }

        if (dirname($packageRoot) !== $extractRoot) {
            return basename($packageRoot);
        }

        return Str::studly((string) ($manifest['alias'] ?? 'plugin'));
    }

    private function publishPackageDirectory(string $sourcePath, string $targetPath, string $baseDirectory): void
    {
        $normalizedBase = str_replace('\\', '/', realpath($baseDirectory) ?: $baseDirectory);
        $normalizedTarget = str_replace('\\', '/', $targetPath);

        if (! str_starts_with($normalizedTarget, rtrim($normalizedBase, '/') . '/')) {
            throw new RuntimeException('Target path is outside the allowed plugin directory.');
        }

        if ($this->files->isDirectory($targetPath)) {
            $this->files->deleteDirectory($targetPath);
        }

        $this->files->ensureDirectoryExists(dirname($targetPath));

        if (! $this->files->copyDirectory($sourcePath, $targetPath)) {
            throw new RuntimeException('Unable to publish the plugin package.');
        }
    }

    private function deletePluginDirectory(PluginManifest $plugin): void
    {
        $pluginPath = str_replace('\\', '/', realpath($plugin->getPath()) ?: $plugin->getPath());
        $allowedBases = collect(config('plugins.scan_paths', []))
            ->filter(static fn (mixed $path): bool => is_string($path) && trim($path) !== '')
            ->map(static fn (string $path): string => str_replace('\\', '/', realpath($path) ?: $path))
            ->values();

        $isAllowed = $allowedBases->contains(
            static fn (string $base): bool => str_starts_with($pluginPath, rtrim($base, '/') . '/'),
        );

        if (! $isAllowed) {
            throw new RuntimeException('Plugin path is outside the allowed plugin directories.');
        }

        if ($this->files->isDirectory($plugin->getPath())) {
            $this->files->deleteDirectory($plugin->getPath());
        }
    }

    private function runInstallBootstrap(PluginManifest $plugin): void
    {
        $install = $plugin->getInstall();
        $migrationPaths = $this->installMigrationPaths($plugin, $install);

        foreach ($migrationPaths as $migrationPath) {
            Artisan::call('migrate', [
                '--path' => $this->relativeArtisanPath($migrationPath),
                '--force' => true,
            ]);
        }

        foreach ((array) ($install['commands'] ?? []) as $command) {
            if (is_string($command) && trim($command) !== '') {
                Artisan::call(trim($command));

                continue;
            }

            if (! is_array($command) || trim((string) ($command['name'] ?? '')) === '') {
                continue;
            }

            Artisan::call(
                (string) $command['name'],
                is_array($command['arguments'] ?? null) ? $command['arguments'] : [],
            );
        }
    }

    /**
     * @param  array<string, mixed>  $install
     * @return array<int, string>
     */
    private function installMigrationPaths(PluginManifest $plugin, array $install): array
    {
        $configuredPaths = collect((array) ($install['migrations'] ?? []))
            ->filter(static fn (mixed $path): bool => is_string($path) && trim($path) !== '')
            ->map(fn (string $path): string => $this->resolvePluginInstallPath($plugin, $path))
            ->filter(fn (string $path): bool => $this->files->exists($path))
            ->values()
            ->all();

        if ($configuredPaths !== []) {
            return $configuredPaths;
        }

        $defaultMigrationPath = $plugin->getPath() . DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR . 'migrations';

        return $this->files->isDirectory($defaultMigrationPath)
            ? [$defaultMigrationPath]
            : [];
    }

    private function resolvePluginInstallPath(PluginManifest $plugin, string $path): string
    {
        if ($this->isAbsolutePath($path)) {
            return $path;
        }

        return $plugin->getPath() . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
    }

    private function relativeArtisanPath(string $path): string
    {
        $normalizedBase = str_replace('\\', '/', realpath(base_path()) ?: base_path());
        $normalizedPath = str_replace('\\', '/', realpath($path) ?: $path);

        if (! str_starts_with($normalizedPath, rtrim($normalizedBase, '/') . '/')) {
            throw new RuntimeException('Install migration path must stay inside the project.');
        }

        return ltrim(substr($normalizedPath, strlen($normalizedBase)), '/');
    }

    private function isAbsolutePath(string $path): bool
    {
        return str_starts_with($path, DIRECTORY_SEPARATOR)
            || preg_match('/^[A-Za-z]:[\\\\\\/]/', $path) === 1;
    }
}
