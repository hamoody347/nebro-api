<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Central\SocialIdentity;
use App\Models\Central\User as CentralUser;
use App\Models\Tenant\User as TenantUser;
use App\Services\SSOManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class SSOController extends Controller
{
    public function __construct(private readonly SSOManager $ssoManager) {}

    /**
     * Return the OAuth2 redirect URL for the given provider.
     * Stores state in session for CSRF validation on callback.
     * Requires X-Tenant-ID header.
     */
    public function redirect(string $provider, Request $request): JsonResponse
    {
        if (! $this->ssoManager->supports($provider)) {
            throw ValidationException::withMessages([
                'provider' => ["Unsupported SSO provider: [{$provider}]."],
            ]);
        }

        $state = encrypt(json_encode([
            'csrf'      => $request->session()->token(),
            'tenant_id' => tenancy()->initialized() ? tenancy()->tenant->id : null,
        ]));

        $request->session()->put("sso.{$provider}.state", $state);

        $redirectUrl = $this->ssoManager->make($provider)->getRedirectUrl($state);

        return response()->json(['redirect_url' => $redirectUrl]);
    }

    /**
     * Handle the OAuth2 callback, provision users, and issue a session + PAT.
     */
    public function callback(string $provider, Request $request): JsonResponse
    {
        $request->validate([
            'code'  => ['required', 'string'],
            'state' => ['required', 'string'],
        ]);

        // Validate state to prevent CSRF.
        $sessionState = $request->session()->pull("sso.{$provider}.state");

        if (! $sessionState || $sessionState !== $request->input('state')) {
            throw ValidationException::withMessages([
                'state' => ['Invalid or expired SSO state parameter.'],
            ]);
        }

        $payload = json_decode(decrypt($request->input('state')), true);

        if (($payload['csrf'] ?? null) !== $request->session()->token()) {
            throw ValidationException::withMessages([
                'state' => ['CSRF token mismatch.'],
            ]);
        }

        $ssoUser = $this->ssoManager->make($provider)->exchangeCodeForUser($request->input('code'));

        // 1. Upsert central User.
        $centralUser = CentralUser::firstOrCreate(
            ['email' => $ssoUser->email],
            ['name'  => $ssoUser->name],
        );

        // 2. Upsert SocialIdentity (never overwrite refresh_token with null).
        $identity = SocialIdentity::firstOrNew([
            'provider'    => $ssoUser->provider,
            'provider_id' => $ssoUser->providerId,
        ]);

        $identity->fill([
            'user_id'          => $centralUser->id,
            'provider_email'   => $ssoUser->email,
            'access_token'     => $ssoUser->accessToken,
            'token_expires_at' => $ssoUser->tokenExpiresAt,
            'raw_data'         => $ssoUser->rawData,
        ]);

        if ($ssoUser->refreshToken !== null) {
            $identity->refresh_token = $ssoUser->refreshToken;
        }

        $identity->save();

        // 3. Provision or resolve tenant user (requires tenant context).
        if (! tenancy()->initialized()) {
            return response()->json(['error' => 'Tenant context required for SSO login.'], 422);
        }

        $tenantUser = TenantUser::firstOrCreate(
            ['email' => $ssoUser->email],
            [
                'central_user_id' => $centralUser->id,
                'name'            => $ssoUser->name,
                'avatar_url'      => $ssoUser->avatarUrl,
            ],
        );

        // 4. Issue session cookie + PAT.
        Auth::login($tenantUser);
        $request->session()->regenerate();

        $token = $tenantUser->createToken('sso-' . $provider)->plainTextToken;

        return response()->json([
            'user'  => $tenantUser,
            'token' => $token,
        ]);
    }
}
