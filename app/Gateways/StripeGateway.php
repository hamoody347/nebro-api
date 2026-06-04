<?php

declare(strict_types=1);

namespace App\Gateways;

use App\Contracts\Payment\NormalisedWebhookPayload;
use App\Contracts\Payment\PaymentData;
use App\Contracts\Payment\PaymentGateway;
use App\Contracts\Payment\PaymentResult;
use App\Contracts\Payment\RefundResult;
use App\Contracts\Payment\SubscriptionData;
use App\Contracts\Payment\SubscriptionResult;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class StripeGateway implements PaymentGateway
{
    public function __construct(private readonly array $config) {}

    public function getName(): string
    {
        return 'stripe';
    }

    public function charge(PaymentData $data): PaymentResult
    {
        $response = $this->client()
            ->withHeader('Idempotency-Key', $data->idempotencyKey)
            ->asForm()
            ->post('/payment_intents', [
                'amount'               => $data->amount,
                'currency'             => $data->currency,
                'customer'             => $data->customerId,
                'payment_method'       => $data->paymentMethodId,
                'description'          => $data->description,
                'metadata'             => $data->metadata,
                'confirm'              => true,
                'return_url'           => config('app.url'),
            ])
            ->throw()
            ->json();

        return new PaymentResult(
            success:          in_array($response['status'], ['succeeded', 'requires_capture']),
            transactionId:    $response['id'],
            gatewayPaymentId: $response['id'],
            amount:           $response['amount'],
            currency:         $response['currency'],
            rawResponse:      $response,
        );
    }

    public function refund(string $transactionId, int $amount, string $currency): RefundResult
    {
        $response = $this->client()
            ->asForm()
            ->post('/refunds', [
                'payment_intent' => $transactionId,
                'amount'         => $amount,
            ])
            ->throw()
            ->json();

        return new RefundResult(
            success:     $response['status'] === 'succeeded',
            refundId:    $response['id'],
            amount:      $response['amount'],
            currency:    $response['currency'],
            status:      $response['status'],
            rawResponse: $response,
        );
    }

    public function createSubscription(SubscriptionData $data): SubscriptionResult
    {
        $payload = [
            'customer' => $data->customerId,
            'items'    => [['price' => $data->priceId]],
            'metadata' => $data->metadata,
        ];

        if ($data->paymentMethodId) {
            $payload['default_payment_method'] = $data->paymentMethodId;
        }

        $response = $this->client()
            ->withHeader('Idempotency-Key', $data->idempotencyKey)
            ->asForm()
            ->post('/subscriptions', $payload)
            ->throw()
            ->json();

        return new SubscriptionResult(
            success:        in_array($response['status'], ['active', 'trialing']),
            subscriptionId: $response['id'],
            status:         $response['status'],
            rawResponse:    $response,
        );
    }

    public function cancelSubscription(string $subscriptionId): bool
    {
        $response = $this->client()
            ->delete("/subscriptions/{$subscriptionId}")
            ->throw()
            ->json();

        return $response['status'] === 'canceled';
    }

    /**
     * Verify the Stripe-Signature header and return a normalised payload.
     * Throws RuntimeException on signature mismatch.
     */
    public function constructWebhookEvent(string $payload, string $signature, string $secret): NormalisedWebhookPayload
    {
        $this->verifyStripeSignature($payload, $signature, $secret);

        $event = json_decode($payload, true);

        return new NormalisedWebhookPayload(
            gateway:    'stripe',
            eventType:  $event['type'],
            eventId:    $event['id'],
            resourceId: $event['data']['object']['id'] ?? null,
            data:       $event['data']['object'] ?? [],
            rawEvent:   $event,
        );
    }

    private function verifyStripeSignature(string $payload, string $sigHeader, string $secret): void
    {
        // Stripe-Signature format: t=<timestamp>,v1=<hmac>,...
        $parts = [];
        foreach (explode(',', $sigHeader) as $pair) {
            [$key, $value] = explode('=', $pair, 2);
            $parts[$key][] = $value;
        }

        $timestamp = $parts['t'][0] ?? null;
        $signatures = $parts['v1'] ?? [];

        if (! $timestamp || empty($signatures)) {
            throw new RuntimeException('Invalid Stripe-Signature header format.');
        }

        // Reject webhooks older than 5 minutes.
        if (abs(time() - (int) $timestamp) > 300) {
            throw new RuntimeException('Stripe webhook timestamp is too old.');
        }

        $signedPayload = $timestamp . '.' . $payload;
        $expected      = hash_hmac('sha256', $signedPayload, $secret);

        foreach ($signatures as $sig) {
            if (hash_equals($expected, $sig)) {
                return;
            }
        }

        throw new RuntimeException('Stripe webhook signature verification failed.');
    }

    private function client(): PendingRequest
    {
        return Http::baseUrl('https://api.stripe.com/v1')
            ->withBasicAuth($this->config['secret_key'], '');
    }
}
