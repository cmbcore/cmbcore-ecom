<?php

declare(strict_types=1);

namespace Modules\Address\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Address\Console\SyncAddressCommand;

class AddressServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
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

        if ($this->app->runningInConsole()) {
            $this->commands([
                SyncAddressCommand::class,
            ]);
        }
    }
}
