<?php

declare(strict_types=1);

namespace Modules\PluginManager\Providers;

use Illuminate\Support\ServiceProvider;

class PluginManagerServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../Config/config.php', 'plugin-manager');
    }

    public function boot(): void
    {
        $apiRoutes = __DIR__ . '/../Routes/api.php';

        if (is_file($apiRoutes)) {
            $this->loadRoutesFrom($apiRoutes);
        }
    }
}
