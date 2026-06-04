<?php

use Illuminate\Support\Facades\Route;

// ── Health check (central, no tenant context required) ────────────────────────
Route::get('/health', fn () => response()->json(['status' => 'ok', 'ts' => now()->toIso8601String()]));

Route::prefix('v1')->group(function () {

    // ── Auth (all require X-Tenant-ID header) ─────────────────────────────────
    Route::prefix('auth')->middleware('tenant')->group(function () {
        Route::post('login',    [\App\Http\Controllers\Auth\AuthController::class, 'login']);
        Route::post('register', [\App\Http\Controllers\Auth\AuthController::class, 'register']);

        // Google SSO
        Route::get('{provider}/redirect', [\App\Http\Controllers\Auth\SSOController::class, 'redirect']);
        Route::get('{provider}/callback', [\App\Http\Controllers\Auth\SSOController::class, 'callback']);

        // Protected auth routes
        Route::middleware(['auth:sanctum', 'set_team'])->group(function () {
            Route::post('logout', [\App\Http\Controllers\Auth\AuthController::class, 'logout']);
            Route::get('user',   [\App\Http\Controllers\Auth\AuthController::class, 'user']);
        });
    });

    // Tenant-scoped business routes live in routes/tenant.php.
    // They are loaded by TenancyServiceProvider::mapRoutes() with
    // [api, tenant, auth:sanctum, set_team] middleware pre-applied.
});
