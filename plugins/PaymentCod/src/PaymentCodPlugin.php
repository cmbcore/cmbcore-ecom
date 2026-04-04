<?php

declare(strict_types=1);

namespace Plugins\PaymentCod;

use App\Core\Plugin\Contracts\PluginInterface;
use App\Core\Plugin\HookManager;
use App\Models\InstalledPlugin;
use Modules\Order\Models\Order;
use Modules\Payment\Contracts\PaymentGatewayInterface;
use Modules\Payment\Data\PaymentResult;
use Modules\Payment\Models\PaymentTransaction;

class PaymentCodPlugin implements PluginInterface
{
    public function boot(HookManager $hooks): void
    {
        $hooks->filter('payment.gateways', function (array $gateways): array {
            if (! $this->isEnabled()) {
                return $gateways;
            }

            $gateways[] = new class($this->settings()) implements PaymentGatewayInterface
            {
                /**
                 * @param  array<string, mixed>  $settings
                 */
                public function __construct(
                    private readonly array $settings,
                ) {
                }

                public function code(): string
                {
                    return Order::PAYMENT_METHOD_COD;
                }

                public function label(): string
                {
                    return 'Thanh toán khi nhận hàng';
                }

                public function description(): ?string
                {
                    return (string) ($this->settings['instructions'] ?? 'Thanh toán khi nhận hàng.');
                }

                public function processPayment(Order $order, array $context = []): PaymentResult
                {
                    return new PaymentResult(
                        transactionStatus: PaymentTransaction::STATUS_AWAITING_CONFIRMATION,
                        orderPaymentStatus: Order::PAYMENT_STATUS_COD_PENDING,
                        reference: 'COD-' . $order->order_number,
                        message: (string) ($this->settings['instructions'] ?? 'Thanh toán khi nhận hàng.'),
                        meta: [
                            'instructions' => (string) ($this->settings['instructions'] ?? 'Thanh toán khi nhận hàng.'),
                        ],
                    );
                }

                public function handleCallback(array $payload): ?PaymentTransaction
                {
                    return null;
                }

                public function refund(Order $order, ?PaymentTransaction $transaction, float $amount, array $context = []): PaymentResult
                {
                    return new PaymentResult(
                        transactionStatus: PaymentTransaction::STATUS_REFUNDED,
                        orderPaymentStatus: Order::PAYMENT_STATUS_UNPAID,
                        reference: $transaction?->reference,
                        message: 'Da ghi nhan hoan tien COD.',
                        meta: [
                            'refund_amount' => $amount,
                            'reason' => $context['reason'] ?? null,
                        ],
                    );
                }
            };

            return $gateways;
        });
    }

    public function activate(): void
    {
    }

    public function deactivate(): void
    {
    }

    public function uninstall(): void
    {
    }

    /**
     * @return array<string, mixed>
     */
    private function settings(): array
    {
        $plugin = InstalledPlugin::query()->where('alias', 'payment-cod')->first();

        return is_array($plugin?->settings) ? $plugin->settings : [];
    }

    private function isEnabled(): bool
    {
        return (bool) ($this->settings()['enabled'] ?? true);
    }
}
