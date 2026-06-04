<?php

declare(strict_types=1);

namespace App\Contracts\Payment;

readonly class PaymentResult
{
    public function __construct(
        public bool   $success,
        public string $transactionId,
        public string $gatewayPaymentId,
        public int    $amount,
        public string $currency,
        public array  $rawResponse = [],
    ) {}
}
