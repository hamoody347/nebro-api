<?php

declare(strict_types=1);

namespace App\Contracts\Payment;

readonly class PaymentData
{
    public function __construct(
        public int     $amount,          // in cents
        public string  $currency,        // ISO 4217 e.g. 'usd'
        public string  $customerId,
        public string  $paymentMethodId,
        public string  $idempotencyKey,  // required — prevents duplicate charges on retry
        public string  $description = '',
        public array   $metadata = [],
    ) {}
}
