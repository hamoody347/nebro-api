<?php

declare(strict_types=1);

return [
    'default' => env('PAYMENT_GATEWAY', 'stripe'),

    'gateways' => [
        'stripe' => [
            'secret_key'      => env('STRIPE_SECRET_KEY'),
            'publishable_key' => env('STRIPE_PUBLISHABLE_KEY'),
            'webhook_secret'  => env('STRIPE_WEBHOOK_SECRET'),
        ],
    ],
];
