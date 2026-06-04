<?php

declare(strict_types=1);

namespace App\Contracts\Payment;

readonly class SubscriptionResult
{
    public function __construct(
        public bool   $success,
        public string $subscriptionId,
        public string $status,
        public array  $rawResponse = [],
    ) {}
}
