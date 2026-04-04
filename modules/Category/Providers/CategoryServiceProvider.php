<?php

declare(strict_types=1);

namespace Modules\Category\Providers;

use Modules\Category\Services\CategoryService;
use Illuminate\Support\ServiceProvider;

class CategoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../Config/config.php', 'category');
        $this->app->singleton(CategoryService::class);
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
