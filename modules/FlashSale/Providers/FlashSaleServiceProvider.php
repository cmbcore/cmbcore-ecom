<?php

declare(strict_types=1);

namespace Modules\FlashSale\Providers;

use App\Core\Plugin\HookManager;
use Illuminate\Support\ServiceProvider;
use Modules\FlashSale\Services\FlashSaleService;
use Modules\Order\Models\Order;

class FlashSaleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../Config/config.php', 'flash_sale');
        $this->app->singleton(FlashSaleService::class);
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
        $flashSaleService = $this->app->make(FlashSaleService::class);

        $hooks->register('order.confirmed', function (Order $order) use ($flashSaleService): void {
            $flashSaleService->recordConfirmedOrder($order);
        });

        $hooks->register('order.cancelled', function (Order $order) use ($flashSaleService): void {
            $flashSaleService->restoreCancelledOrder($order);
        });
    }
}
