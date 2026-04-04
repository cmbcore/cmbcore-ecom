<?php

declare(strict_types=1);

namespace Modules\Product\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Product\Services\MediaUploadService;
use Modules\Product\Services\ProductCatalogService;
use Modules\Product\Services\ProductService;

class ProductServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../Config/config.php', 'product');
        $this->app->singleton(MediaUploadService::class);
        $this->app->singleton(ProductCatalogService::class);
        $this->app->singleton(ProductService::class);
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
