<?php

declare(strict_types=1);

namespace Modules\Payment\Contracts;

use Modules\Order\Models\Order;
use Modules\Payment\Data\PaymentResult;
use Modules\Payment\Models\PaymentTransaction;

interface PaymentGatewayInterface
{
    public function code(): string;

    public function label(): string;

    public function description(): ?string;

    public function processPayment(Order $order, array $context = []): PaymentResult;

    public function handleCallback(array $payload): ?PaymentTransaction;

    public function refund(Order $order, ?PaymentTransaction $transaction, float $amount, array $context = []): PaymentResult;
}
