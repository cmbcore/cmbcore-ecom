<?php

declare(strict_types=1);

namespace Modules\Inventory\Providers;

use App\Core\Plugin\HookManager;
use Illuminate\Support\ServiceProvider;
use Modules\Inventory\Services\InventoryService;
use Modules\Order\Models\Order;

class InventoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../Config/config.php', 'inventory');
        $this->app->singleton(InventoryService::class);
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

        $hooks = $this->app->make(HookManager::class);
        $inventoryService = $this->app->make(InventoryService::class);

        $hooks->register('order.confirmed', function (Order $order) use ($inventoryService): void {
            $inventoryService->deductForOrder($order);
        });

        $hooks->register('order.cancelled', function (Order $order) use ($inventoryService): void {
            $inventoryService->restoreForOrder($order);
        });
    }
}
