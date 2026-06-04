<?php

return [

    'defaults' => [
        'guard'     => env('AUTH_GUARD', 'web'),
        'passwords' => env('AUTH_PASSWORD_BROKER', 'users'),
    ],

    'guards' => [
        // Primary guard: tenant-scoped users authenticated via session (Sanctum cookie) or Bearer token.
        // All API routes that call auth:sanctum use this guard's provider.
        'web' => [
            'driver'   => 'session',
            'provider' => 'tenant_users',
        ],
    ],

    'providers' => [
        // Central identity — used by central admin routes (if added later).
        'users' => [
            'driver' => 'eloquent',
            'model'  => App\Models\Central\User::class,
        ],

        // Tenant-scoped users — authenticates against the tenant DB (stancl switches connection at runtime).
        'tenant_users' => [
            'driver' => 'eloquent',
            'model'  => App\Models\Tenant\User::class,
        ],
    ],

    'passwords' => [
        'users' => [
            'provider' => 'users',
            'table'    => env('AUTH_PASSWORD_RESET_TOKEN_TABLE', 'password_reset_tokens'),
            'expire'   => 60,
            'throttle' => 60,
        ],
    ],

    'password_timeout' => env('AUTH_PASSWORD_TIMEOUT', 10800),

];
