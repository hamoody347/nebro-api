<?php

declare(strict_types=1);

namespace App\Adapters\SSO;

use App\Contracts\SSO\SSOAdapterInterface;
use App\DTOs\SSOUserDTO;
use Illuminate\Http\Client\Pool;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;

class GoogleAdapter implements SSOAdapterInterface
{
    private const TOKEN_ENDPOINT    = 'https://oauth2.googleapis.com/token';
    private const USERINFO_ENDPOINT = 'https://openidconnect.googleapis.com/v1/userinfo';
    private const PEOPLE_ENDPOINT   = 'https://people.googleapis.com/v1/people/me';
    private const REVOKE_ENDPOINT   = 'https://oauth2.googleapis.com/revoke';

    public function __construct(private readonly array $config) {}

    public function getRedirectUrl(string $state): string
    {
        $scopes = implode(' ', $this->config['scopes'] ?? ['openid', 'email', 'profile']);

        return 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query([
            'client_id'     => $this->config['client_id'],
            'redirect_uri'  => $this->config['redirect_uri'],
            'response_type' => 'code',
            'scope'         => $scopes,
            'state'         => $state,
            'access_type'   => 'offline',
            'prompt'        => 'consent', // always request refresh token
        ]);
    }

    public function exchangeCodeForUser(string $code): SSOUserDTO
    {
        $tokens = Http::asForm()->post(self::TOKEN_ENDPOINT, [
            'code'          => $code,
            'client_id'     => $this->config['client_id'],
            'client_secret' => $this->config['client_secret'],
            'redirect_uri'  => $this->config['redirect_uri'],
            'grant_type'    => 'authorization_code',
        ])->throw()->json();

        return $this->fetchUserInfo($tokens);
    }

    public function refreshUserToken(string $encryptedRefreshToken): SSOUserDTO
    {
        $refreshToken = Crypt::decryptString($encryptedRefreshToken);

        $tokens = Http::asForm()->post(self::TOKEN_ENDPOINT, [
            'refresh_token' => $refreshToken,
            'client_id'     => $this->config['client_id'],
            'client_secret' => $this->config['client_secret'],
            'grant_type'    => 'refresh_token',
        ])->throw()->json();

        // Refresh responses don't include a new refresh_token; preserve the original.
        $tokens['refresh_token'] ??= $refreshToken;

        return $this->fetchUserInfo($tokens);
    }

    private function fetchUserInfo(array $tokens): SSOUserDTO
    {
        $accessToken = $tokens['access_token'];
        $personFields = $this->config['people_fields'] ?? 'photos';

        // Parallel HTTP: OpenID userinfo + Google People API
        $responses = Http::pool(fn (Pool $pool) => [
            $pool->as('userinfo')
                 ->withToken($accessToken)
                 ->get(self::USERINFO_ENDPOINT),
            $pool->as('people')
                 ->withToken($accessToken)
                 ->get(self::PEOPLE_ENDPOINT, ['personFields' => $personFields]),
        ]);

        $responses['userinfo']->throw();
        $userinfoData = array_merge($responses['userinfo']->json(), [
            'access_token'  => $accessToken,
            'refresh_token' => $tokens['refresh_token'] ?? null,
            'expires_in'    => $tokens['expires_in'] ?? null,
        ]);

        $peopleData = $responses['people']->successful() ? $responses['people']->json() : [];

        return SSOUserDTO::fromGoogle($userinfoData, $peopleData);
    }
}
