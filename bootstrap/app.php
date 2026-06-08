<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__.'/../routes/api.php',
        apiPrefix: 'api',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            // Webhook routes sit outside Sanctum and tenant middleware.
            Illuminate\Support\Facades\Route::middleware('api')
                ->group(base_path('routes/webhooks.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Sanctum stateful API: enables EnsureFrontendRequestsAreStateful for api/* routes.
        // Supports both cookie-based (SPA) and Bearer token (mobile/API) auth simultaneously.
        $middleware->statefulApi();

        // Named middleware aliases available in route groups.
        $middleware->alias([
            'tenant'             => \Stancl\Tenancy\Middleware\InitializeTenancyByRequestData::class,
            'role'               => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission'         => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
            'set_team'           => \App\Http\Middleware\SetPermissionsTeamId::class,
            'require_module'     => \App\Http\Middleware\RequiresTenantModule::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->is('webhooks/*'),
        );
    })->create();
