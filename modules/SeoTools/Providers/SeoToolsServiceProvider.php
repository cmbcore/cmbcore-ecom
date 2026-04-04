<?php

declare(strict_types=1);

namespace Modules\SeoTools\Providers;

use App\Core\Plugin\HookManager;
use Illuminate\Support\ServiceProvider;
use Modules\SeoTools\Services\SeoToolsService;

class SeoToolsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../Config/config.php', 'seo_tools');
        $this->app->singleton(SeoToolsService::class);
    }

    public function boot(): void
    {
        $apiRoutes = __DIR__ . '/../Routes/api.php';
        $webRoutes = __DIR__ . '/../Routes/web.php';
        $views = __DIR__ . '/../Resources/views';

        if (is_file($apiRoutes)) {
            $this->loadRoutesFrom($apiRoutes);
        }

        if (is_file($webRoutes)) {
            $this->loadRoutesFrom($webRoutes);
        }

        if (is_dir($views)) {
            $this->loadViewsFrom($views, 'seo-tools');
        }

        $hooks = $this->app->make(HookManager::class);
        $service = $this->app->make(SeoToolsService::class);

        $hooks->register('theme.head', fn (): string => $service->renderHeadPayload());
    }
}
