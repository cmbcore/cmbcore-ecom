<?php

declare(strict_types=1);

namespace Modules\Reports\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Reports\Services\ReportsService;

class ReportsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../Config/config.php', 'reports');
        $this->app->singleton(ReportsService::class);
    }

    public function boot(): void
    {
        $apiRoutes = __DIR__ . '/../Routes/api.php';

        if (is_file($apiRoutes)) {
            $this->loadRoutesFrom($apiRoutes);
        }
    }
}
