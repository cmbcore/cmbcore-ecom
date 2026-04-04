<?php

declare(strict_types=1);

namespace Modules\Payment\Data;

final class PaymentResult
{
    /**
     * @param  array<string, mixed>  $meta
     */
    public function __construct(
        public readonly string $transactionStatus,
        public readonly string $orderPaymentStatus,
        public readonly ?string $reference = null,
        public readonly ?string $message = null,
        public readonly array $meta = [],
    ) {
    }
}
