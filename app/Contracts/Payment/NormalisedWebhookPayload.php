<?php

declare(strict_types=1);

namespace App\Contracts\Payment;

readonly class NormalisedWebhookPayload
{
    public function __construct(
        public string  $gateway,
        public string  $eventType,
        public string  $eventId,
        public ?string $resourceId,
        public array   $data,
        public array   $rawEvent = [],
    ) {}
}
