<?php

declare(strict_types=1);

namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use App\Models\Tenant\WebhookLog;
use App\Services\PaymentGatewayManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class WebhookController extends Controller
{
    public function __construct(private readonly PaymentGatewayManager $manager) {}

    /**
     * Route: POST /webhooks/{gateway}/{tenant}
     *
     * No Sanctum or tenant middleware on this route — we initialize tenancy manually
     * here so signature verification happens against the raw (unmodified) body.
     */
    public function handle(string $gateway, string $tenant, Request $request): JsonResponse
    {
        // 1. Initialize tenant context so we can write to the tenant DB.
        try {
            tenancy()->initialize($tenant);
        } catch (\Throwable) {
            return response()->json(['error' => 'Unknown tenant.'], 404);
        }

        // 2. Read the raw body BEFORE any further parsing.
        $rawPayload = $request->getContent();
        $signature  = $request->header('Stripe-Signature', '');

        $gatewayConfig = config("payment.gateways.{$gateway}");
        if (! $gatewayConfig) {
            return response()->json(['error' => "Unknown gateway [{$gateway}]."], 404);
        }

        // 3. Verify signature and parse the normalised payload.
        try {
            $normalised = $this->manager->driver($gateway)->constructWebhookEvent(
                $rawPayload,
                $signature,
                $gatewayConfig['webhook_secret'],
            );
        } catch (RuntimeException $e) {
            return response()->json(['error' => 'Webhook signature verification failed.'], 403);
        }

        // 4. Log the raw payload to the tenant DB.
        WebhookLog::create([
            'gateway'    => $gateway,
            'event_type' => $normalised->eventType,
            'payload'    => json_decode($rawPayload, true),
            'signature'  => $signature,
        ]);

        // 5. Dispatch a queued job for domain processing (job implementation is business-logic — add later).
        // ProcessWebhookEvent::dispatch($normalised);

        return response()->json(['status' => 'accepted'], 202);
    }
}
