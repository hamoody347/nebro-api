<?php

declare(strict_types=1);

namespace App\Contracts\Payment;

readonly class SubscriptionData
{
    public function __construct(
        public string  $customerId,
        public string  $priceId,
        public string  $idempotencyKey,
        public ?string $paymentMethodId = null,
        public array   $metadata = [],
    ) {}
}
