<?php

declare(strict_types=1);

namespace Modules\ThemeManager\Providers;

use Illuminate\Support\ServiceProvider;

class ThemeManagerServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../Config/config.php', 'theme-manager');
    }

    public function boot(): void
    {
        $apiRoutes = __DIR__ . '/../Routes/api.php';

        if (is_file($apiRoutes)) {
            $this->loadRoutesFrom($apiRoutes);
        }
    }
}
