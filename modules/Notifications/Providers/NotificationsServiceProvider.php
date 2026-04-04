<?php

declare(strict_types=1);

namespace Modules\Notifications\Providers;

use App\Core\Plugin\HookManager;
use Illuminate\Support\ServiceProvider;
use Modules\Notifications\Services\NotificationTemplateService;
use Modules\Order\Models\Order;

class NotificationsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../Config/config.php', 'notifications');
        $this->app->singleton(NotificationTemplateService::class);
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

        $service = $this->app->make(NotificationTemplateService::class);
        $service->ensureDefaults();
        $hooks = $this->app->make(HookManager::class);

        $hooks->register('customer.registered', function ($user) use ($service): void {
            $service->send('customer_registered', $user->email, [
                'customer_name' => $user->name,
            ]);
        });

        $hooks->register('order.created', function (Order $order) use ($service): void {
            $service->send('order_created_customer', $order->guest_email ?: $order->user?->email, [
                'customer_name' => $order->customer_name,
                'order_number' => $order->order_number,
                'grand_total' => $order->grand_total,
                'payment_status' => $order->payment_status,
            ]);

            $service->send('order_created_admin', (string) config('mail.from.address'), [
                'customer_name' => $order->customer_name,
                'order_number' => $order->order_number,
                'grand_total' => $order->grand_total,
            ]);
        });

        $statusMailer = function (string $type) use ($service): callable {
            return function (Order $order) use ($service, $type): void {
                $service->send($type, $order->guest_email ?: $order->user?->email, [
                    'customer_name' => $order->customer_name,
                    'order_number' => $order->order_number,
                    'grand_total' => $order->grand_total,
                    'order_status' => $order->order_status,
                    'fulfillment_status' => $order->fulfillment_status,
                ]);
            };
        };

        $hooks->register('order.confirmed', $statusMailer('order_confirmed_customer'));
        $hooks->register('order.cancelled', $statusMailer('order_cancelled_customer'));
        $hooks->register('order.delivered', $statusMailer('order_delivered_customer'));
    }
}
