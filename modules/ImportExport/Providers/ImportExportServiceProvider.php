<?php

declare(strict_types=1);

namespace Modules\ImportExport\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\ImportExport\Services\ImportExportService;

class ImportExportServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../Config/config.php', 'import_export');
        $this->app->singleton(ImportExportService::class);
    }

    public function boot(): void
    {
        $apiRoutes = __DIR__ . '/../Routes/api.php';

        if (is_file($apiRoutes)) {
            $this->loadRoutesFrom($apiRoutes);
        }
    }
}
