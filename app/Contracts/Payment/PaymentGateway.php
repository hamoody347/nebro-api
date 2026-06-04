<?php

declare(strict_types=1);

namespace App\Contracts\Payment;

use Illuminate\Http\Request;

interface PaymentGateway
{
    public function getName(): string;

    public function charge(PaymentData $data): PaymentResult;

    public function refund(string $transactionId, int $amount, string $currency): RefundResult;

    public function createSubscription(SubscriptionData $data): SubscriptionResult;

    public function cancelSubscription(string $subscriptionId): bool;

    /**
     * Parse, verify signature, and normalise an inbound webhook payload.
     * Throws on invalid signature.
     */
    public function constructWebhookEvent(string $payload, string $signature, string $secret): NormalisedWebhookPayload;
}
