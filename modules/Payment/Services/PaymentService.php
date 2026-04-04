<?php

declare(strict_types=1);

namespace Modules\Payment\Services;

use App\Core\Plugin\HookManager;
use App\Core\Plugin\PluginManager;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Modules\Order\Models\Order;
use Modules\Payment\Contracts\PaymentGatewayInterface;
use Modules\Payment\Models\PaymentTransaction;

class PaymentService
{
    public function __construct(
        private readonly HookManager $hookManager,
        private readonly PluginManager $pluginManager,
    ) {
    }

    /**
     * @return array<int, PaymentGatewayInterface>
     */
    public function gateways(): array
    {
        $this->pluginManager->bootActivePlugins();

        $gateways = $this->hookManager->applyFilter('payment.gateways', []);

        return array_values(array_filter(
            is_array($gateways) ? $gateways : [],
            static fn (mixed $gateway): bool => $gateway instanceof PaymentGatewayInterface,
        ));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function availableMethods(): array
    {
        return array_map(static fn (PaymentGatewayInterface $gateway): array => [
            'code' => $gateway->code(),
            'label' => $gateway->label(),
            'description' => $gateway->description(),
        ], $this->gateways());
    }

    public function hasGateway(string $code): bool
    {
        return $this->resolveGateway($code) instanceof PaymentGatewayInterface;
    }

    public function defaultMethod(): ?string
    {
        return $this->availableMethods()[0]['code'] ?? null;
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public function process(Order $order, array $context = []): PaymentTransaction
    {
        $gateway = $this->resolveGateway($order->payment_method);

        abort_unless($gateway instanceof PaymentGatewayInterface, 422, 'Phuong thuc thanh toán khong hop le.');

        $result = $gateway->processPayment($order, $context);

        $transaction = PaymentTransaction::query()->create([
            'order_id' => $order->id,
            'gateway' => $gateway->code(),
            'amount' => $order->grand_total,
            'status' => $result->transactionStatus,
            'reference' => $result->reference,
            'meta' => array_replace($result->meta, ['message' => $result->message]),
        ]);

        $order->forceFill([
            'payment_status' => $result->orderPaymentStatus,
            'payment_meta' => array_replace($order->payment_meta ?? [], $result->meta),
        ])->save();

        return $transaction->refresh();
    }

    /**
     * @return LengthAwarePaginator<int, PaymentTransaction>
     */
    public function paginate(array $filters = []): LengthAwarePaginator
    {
        $search = trim((string) ($filters['search'] ?? ''));

        return PaymentTransaction::query()
            ->with('order')
            ->when($search !== '', function (Builder $query) use ($search): void {
                $like = '%' . $search . '%';
                $query->where('reference', 'like', $like)
                    ->orWhere('gateway', 'like', $like)
                    ->orWhereHas('order', fn (Builder $orderQuery) => $orderQuery->where('order_number', 'like', $like));
            })
            ->latest('id')
            ->paginate((int) ($filters['per_page'] ?? config('payment.per_page', 20)));
    }

    public function confirmTransaction(PaymentTransaction $transaction, ?User $actor = null, ?string $note = null): PaymentTransaction
    {
        $transaction->forceFill([
            'status' => PaymentTransaction::STATUS_PAID,
            'confirmed_at' => now(),
            'meta' => array_replace($transaction->meta ?? [], [
                'confirmed_by' => $actor?->id,
                'confirmation_note' => $note,
            ]),
        ])->save();

        $transaction->order->forceFill([
            'payment_status' => Order::PAYMENT_STATUS_PAID,
        ])->save();

        return $transaction->refresh()->load('order');
    }

    public function refund(Order $order, float $amount, ?string $reason = null): PaymentTransaction
    {
        /** @var PaymentTransaction|null $transaction */
        $transaction = $order->payments()->latest('id')->first();
        $gateway = $this->resolveGateway($order->payment_method);

        abort_unless($gateway instanceof PaymentGatewayInterface, 422, 'Khong tim thay gateway de hoan tien.');

        $result = $gateway->refund($order, $transaction, $amount, ['reason' => $reason]);

        if ($transaction instanceof PaymentTransaction) {
            $transaction->forceFill([
                'status' => $result->transactionStatus,
                'refunded_at' => now(),
                'meta' => array_replace($transaction->meta ?? [], $result->meta, ['refund_reason' => $reason]),
            ])->save();

            return $transaction->refresh();
        }

        return PaymentTransaction::query()->create([
            'order_id' => $order->id,
            'gateway' => $gateway->code(),
            'amount' => $amount,
            'status' => $result->transactionStatus,
            'reference' => $result->reference,
            'meta' => array_replace($result->meta, ['refund_reason' => $reason]),
            'refunded_at' => now(),
        ]);
    }

    private function resolveGateway(string $code): ?PaymentGatewayInterface
    {
        foreach ($this->gateways() as $gateway) {
            if ($gateway->code() === $code) {
                return $gateway;
            }
        }

        return null;
    }
}
