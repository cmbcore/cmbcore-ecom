<?php

declare(strict_types=1);

namespace Modules\Page\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Page\Services\PageCatalogService;
use Modules\Page\Services\PageService;

class PageServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../Config/config.php', 'page');
        $this->app->singleton(PageCatalogService::class);
        $this->app->singleton(PageService::class);
    }

    public function boot(): void
    {
        $apiRoutes = __DIR__ . '/../Routes/api.php';
        $webRoutes = __DIR__ . '/../Routes/web.php';
        $migrations = __DIR__ . '/../Database/Migrations';

        if (is_file($apiRoutes)) {
            $this->loadRoutesFrom($apiRoutes);
        }

        if (is_file($webRoutes)) {
            $this->loadRoutesFrom($webRoutes);
        }

        if (is_dir($migrations)) {
            $this->loadMigrationsFrom($migrations);
        }
    }
}
