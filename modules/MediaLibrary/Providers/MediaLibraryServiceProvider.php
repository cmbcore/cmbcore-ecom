<?php

declare(strict_types=1);

namespace Modules\MediaLibrary\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\MediaLibrary\Services\MediaLibraryService;

class MediaLibraryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../Config/config.php', 'media_library');
        $this->app->singleton(MediaLibraryService::class);
    }

    public function boot(): void
    {
        $apiRoutes = __DIR__ . '/../Routes/api.php';
        $migrations = __DIR__ . '/../Database/Migrations';

        if (is_file($apiRoutes)) {
            $this->loadRoutesFrom($apiRoutes);
        }

        if (is_dir($migrations)) {
            $this->loadMigrationsFrom($migrations);
        }
    }
}
