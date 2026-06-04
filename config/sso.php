<?php

declare(strict_types=1);

return [
    'google' => [
        'client_id'      => env('GOOGLE_CLIENT_ID'),
        'client_secret'  => env('GOOGLE_CLIENT_SECRET'),
        'redirect_uri'   => env('GOOGLE_REDIRECT_URI'),
        'scopes'         => ['openid', 'email', 'profile'],
        'people_fields'  => env('GOOGLE_PEOPLE_FIELDS', 'photos,birthdays,genders'),
    ],
];
