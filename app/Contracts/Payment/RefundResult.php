<?php

declare(strict_types=1);

namespace App\Contracts\Payment;

readonly class RefundResult
{
    public function __construct(
        public bool   $success,
        public string $refundId,
        public int    $amount,
        public string $currency,
        public string $status,
        public array  $rawResponse = [],
    ) {}
}
