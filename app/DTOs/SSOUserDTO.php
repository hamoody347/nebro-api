<?php

declare(strict_types=1);

namespace App\DTOs;

use Carbon\Carbon;

readonly class SSOUserDTO
{
    public function __construct(
        public string  $provider,
        public string  $providerId,
        public string  $email,
        public string  $name,
        public ?string $avatarUrl,
        public ?string $accessToken,
        public ?string $refreshToken,
        public ?Carbon $tokenExpiresAt,
        public array   $rawData = [],
    ) {}

    public static function fromGoogle(array $userinfo, array $people = []): static
    {
        $expiresIn = $userinfo['expires_in'] ?? null;

        return new static(
            provider:       'google',
            providerId:     $userinfo['sub'],
            email:          $userinfo['email'],
            name:           $userinfo['name'] ?? ($userinfo['given_name'] ?? ''),
            avatarUrl:      $userinfo['picture'] ?? null,
            accessToken:    $userinfo['access_token'] ?? null,
            refreshToken:   $userinfo['refresh_token'] ?? null,
            tokenExpiresAt: $expiresIn ? Carbon::now()->addSeconds($expiresIn) : null,
            rawData:        ['userinfo' => $userinfo, 'people' => $people],
        );
    }
}
