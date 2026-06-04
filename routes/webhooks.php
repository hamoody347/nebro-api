<?php

use Illuminate\Support\Facades\Route;

// Webhook receiver — no Sanctum, no tenant middleware.
// The controller manually initializes tenancy from the {tenant} path param.
// Signature is verified before any processing via PaymentGatewayManager::driver($gateway)->constructWebhookEvent().
Route::post('{gateway}/{tenant}', [\App\Http\Controllers\Webhook\WebhookController::class, 'handle'])
    ->name('webhooks.handle');
