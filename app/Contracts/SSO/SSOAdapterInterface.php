<?php

declare(strict_types=1);

namespace App\Contracts\SSO;

use App\DTOs\SSOUserDTO;

interface SSOAdapterInterface
{
    /**
     * Build the provider's OAuth2 authorization redirect URL.
     * $state is a signed, opaque string the caller should store in session.
     */
    public function getRedirectUrl(string $state): string;

    /**
     * Exchange the authorization code for user identity.
     * Validates state internally if the provider embeds it in the token response.
     */
    public function exchangeCodeForUser(string $code): SSOUserDTO;

    /**
     * Refresh an expired access token using a previously-stored (encrypted) refresh token.
     * Returns a new SSOUserDTO with updated token fields.
     */
    public function refreshUserToken(string $encryptedRefreshToken): SSOUserDTO;
}
