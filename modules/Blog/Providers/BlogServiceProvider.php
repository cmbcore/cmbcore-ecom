<?php

declare(strict_types=1);

namespace Modules\Blog\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Blog\Services\BlogCatalogService;
use Modules\Blog\Services\BlogCategoryService;
use Modules\Blog\Services\BlogService;

class BlogServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../Config/config.php', 'blog');
        $this->app->singleton(BlogCatalogService::class);
        $this->app->singleton(BlogCategoryService::class);
        $this->app->singleton(BlogService::class);
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
