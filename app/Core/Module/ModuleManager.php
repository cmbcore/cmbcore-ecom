<?php

declare(strict_types=1);

namespace App\Core\Module;

use App\Core\Module\Data\ModuleManifest;
use App\Core\Plugin\HookManager;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class ModuleManager
{
    private ?Collection $modules = null;

    /**
     * @var array<string, bool>|null
     */
    private ?array $statusOverrides = null;

    public function __construct(
        private readonly Filesystem $files,
        private readonly Application $app,
    ) {
    }

    /**
     * @return Collection<int, ModuleManifest>
     */
    public function getAll(): Collection
    {
        if ($this->modules instanceof Collection) {
            return $this->modules;
        }

        $modules = collect();

        foreach (config('modules.scan_paths', []) as $scanPath) {
            if (! is_string($scanPath) || ! $this->files->isDirectory($scanPath)) {
                continue;
            }

            foreach ($this->files->directories($scanPath) as $directory) {
                $manifestPath = $directory . DIRECTORY_SEPARATOR . 'module.json';

                if (! $this->files->exists($manifestPath)) {
                    continue;
                }

                $manifest = $this->loadManifest($manifestPath);

                if ($manifest instanceof ModuleManifest) {
                    $modules->push($this->applyRuntimeStatus($manifest));
                }
            }
        }

        $this->modules = $this->sortByDependencies($modules)->values();

        return $this->modules;
    }

    /**
     * @return Collection<int, ModuleManifest>
     */
    public function getEnabled(): Collection
    {
        $modules = $this->getAll()->keyBy(static fn (ModuleManifest $module): string => $module->getAlias());

        return $modules
            ->filter(function (ModuleManifest $module) use ($modules): bool {
                if (! $module->isEnabled()) {
                    return false;
                }

                foreach ($module->getDependencies() as $dependency) {
                    $dependencyModule = $modules->get($dependency);

                    if (! $dependencyModule instanceof ModuleManifest || ! $dependencyModule->isEnabled()) {
                        return false;
                    }
                }

                return true;
            })
            ->values();
    }

    public function find(string $alias): ?ModuleManifest
    {
        return $this->getAll()->first(
            static fn (ModuleManifest $module): bool => $module->getAlias() === $alias,
        );
    }

    public function enable(string $alias): void
    {
        $module = $this->find($alias);

        if (! $module instanceof ModuleManifest) {
            throw new RuntimeException("Module [{$alias}] was not found.");
        }

        foreach ($module->getDependencies() as $dependency) {
            $dependencyModule = $this->find($dependency);

            if (! $dependencyModule instanceof ModuleManifest || ! $dependencyModule->isEnabled()) {
                throw new RuntimeException("Module [{$alias}] requires enabled dependency [{$dependency}].");
            }
        }

        $overrides = $this->loadStatusOverrides();
        $overrides[$alias] = true;
        $this->persistStatusOverrides($overrides);
        $this->flush();
    }

    public function disable(string $alias): void
    {
        $module = $this->find($alias);

        if (! $module instanceof ModuleManifest) {
            throw new RuntimeException("Module [{$alias}] was not found.");
        }

        $dependentModules = $this->getEnabled()->filter(
            static fn (ModuleManifest $enabledModule): bool => in_array($alias, $enabledModule->getDependencies(), true),
        );

        if ($dependentModules->isNotEmpty()) {
            $aliases = $dependentModules
                ->map(static fn (ModuleManifest $enabledModule): string => $enabledModule->getAlias())
                ->implode(', ');

            throw new RuntimeException("Cannot disable [{$alias}] while dependent modules are enabled: {$aliases}.");
        }

        $overrides = $this->loadStatusOverrides();
        $overrides[$alias] = false;
        $this->persistStatusOverrides($overrides);
        $this->flush();
    }

    public function isEnabled(string $alias): bool
    {
        return $this->getEnabled()->contains(
            static fn (ModuleManifest $module): bool => $module->getAlias() === $alias,
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getAdminMenus(): array
    {
        $menus = [
            [
                'label' => __('admin.modules.dashboard'),
                'icon' => 'fa-solid fa-gauge-high',
                'route' => '/admin',
            ],
        ];

        foreach ($this->getEnabled() as $module) {
            foreach ($module->getAdminMenu() as $menuItem) {
                $menus[] = $this->translateMenuItem($menuItem);
            }
        }

        /** @var array<int, array<string, mixed>> $menus */
        $menus = app(HookManager::class)->applyFilter('admin.menu', $menus);

        return $menus;
    }

    /**
     * @return array<string, string>
     */
    public function getAdminPages(): array
    {
        $pages = [];

        foreach ($this->getEnabled() as $module) {
            $pages = array_merge($pages, $module->getAdminPages());
        }

        return $pages;
    }

    public function registerEnabledProviders(): void
    {
        foreach ($this->getEnabled() as $module) {
            foreach ($module->getProviders() as $provider) {
                if (! class_exists($provider)) {
                    Log::warning('Skipping module provider because the class does not exist.', [
                        'module' => $module->getAlias(),
                        'provider' => $provider,
                    ]);

                    continue;
                }

                $this->app->register($provider);
            }
        }
    }

    public function migrate(string $alias): int
    {
        $module = $this->find($alias);

        if (! $module instanceof ModuleManifest) {
            throw new RuntimeException("Module [{$alias}] was not found.");
        }

        $migrationPath = $module->getPath() . DIRECTORY_SEPARATOR . 'Database' . DIRECTORY_SEPARATOR . 'Migrations';

        if (! $this->files->isDirectory($migrationPath)) {
            throw new RuntimeException("Module [{$alias}] does not define a migration path.");
        }

        return Artisan::call('migrate', [
            '--path' => $migrationPath,
            '--realpath' => true,
            '--force' => true,
        ]);
    }

    public function rollback(string $alias): int
    {
        $module = $this->find($alias);

        if (! $module instanceof ModuleManifest) {
            throw new RuntimeException("Module [{$alias}] was not found.");
        }

        $migrationPath = $module->getPath() . DIRECTORY_SEPARATOR . 'Database' . DIRECTORY_SEPARATOR . 'Migrations';

        if (! $this->files->isDirectory($migrationPath)) {
            throw new RuntimeException("Module [{$alias}] does not define a migration path.");
        }

        return Artisan::call('migrate:rollback', [
            '--path' => $migrationPath,
            '--realpath' => true,
            '--force' => true,
        ]);
    }

    public function flush(): void
    {
        $this->modules = null;
        $this->statusOverrides = null;
    }

    private function loadManifest(string $manifestPath): ?ModuleManifest
    {
        try {
            /** @var array<string, mixed> $data */
            $data = json_decode($this->files->get($manifestPath), true, 512, JSON_THROW_ON_ERROR);

            return ModuleManifest::fromArray($data, dirname($manifestPath));
        } catch (Throwable $exception) {
            Log::warning('Failed to load module manifest.', [
                'manifest' => $manifestPath,
                'error' => $exception->getMessage(),
            ]);

            return null;
        }
    }

    private function applyRuntimeStatus(ModuleManifest $module): ModuleManifest
    {
        $overrides = $this->loadStatusOverrides();

        if (! array_key_exists($module->getAlias(), $overrides)) {
            return $module;
        }

        return $module->withEnabled($overrides[$module->getAlias()]);
    }

    /**
     * @param  Collection<int, ModuleManifest>  $modules
     * @return Collection<int, ModuleManifest>
     */
    private function sortByDependencies(Collection $modules): Collection
    {
        $indexed = $modules
            ->sortBy(static fn (ModuleManifest $module): int => $module->getOrder())
            ->keyBy(static fn (ModuleManifest $module): string => $module->getAlias());

        $sorted = collect();
        $visiting = [];
        $visited = [];

        $visit = function (ModuleManifest $module) use (&$visit, &$indexed, &$sorted, &$visiting, &$visited): void {
            $alias = $module->getAlias();

            if (isset($visited[$alias])) {
                return;
            }

            if (isset($visiting[$alias])) {
                throw new RuntimeException("Circular module dependency detected at [{$alias}].");
            }

            $visiting[$alias] = true;

            foreach ($module->getDependencies() as $dependency) {
                $dependencyModule = $indexed->get($dependency);

                if ($dependencyModule instanceof ModuleManifest) {
                    $visit($dependencyModule);
                }
            }

            unset($visiting[$alias]);
            $visited[$alias] = true;
            $sorted->put($alias, $module);
        };

        foreach ($indexed as $module) {
            $visit($module);
        }

        return $sorted->values();
    }

    /**
     * @return array<string, bool>
     */
    private function loadStatusOverrides(): array
    {
        if (is_array($this->statusOverrides)) {
            return $this->statusOverrides;
        }

        $statusFile = config('modules.status_file');

        if (! is_string($statusFile) || ! $this->files->exists($statusFile)) {
            return $this->statusOverrides = [];
        }

        try {
            /** @var array<string, bool> $overrides */
            $overrides = json_decode($this->files->get($statusFile), true, 512, JSON_THROW_ON_ERROR);

            return $this->statusOverrides = $overrides;
        } catch (Throwable $exception) {
            Log::warning('Failed to load module status overrides.', [
                'file' => $statusFile,
                'error' => $exception->getMessage(),
            ]);

            return $this->statusOverrides = [];
        }
    }

    /**
     * @param  array<string, bool>  $overrides
     */
    private function persistStatusOverrides(array $overrides): void
    {
        $statusFile = (string) config('modules.status_file');

        $this->files->ensureDirectoryExists(dirname($statusFile));
        $this->files->put($statusFile, json_encode($overrides, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
        $this->statusOverrides = $overrides;
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
}
