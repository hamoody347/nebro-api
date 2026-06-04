<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Central\User as CentralUser;
use App\Models\Tenant\User as TenantUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Authenticate a tenant user and issue both a session cookie and a personal access token.
     * Both cookie (SPA) and Bearer token (mobile/API) mechanisms are active simultaneously.
     *
     * Requires X-Tenant-ID header (tenant middleware must be applied on this route).
     */
    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials)) {
            throw ValidationException::withMessages([
                'email' => [__('auth.failed')],
            ]);
        }

        /** @var TenantUser $user */
        $user = Auth::user();

        $request->session()->regenerate();

        $token = $user->createToken('api')->plainTextToken;

        return response()->json([
            'user'  => $user,
            'token' => $token,
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        // Revoke the current PAT if the request was authenticated via Bearer token.
        $request->user()?->currentAccessToken()?->delete();

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['message' => 'Logged out.']);
    }

    /**
     * Register a new user in the current tenant context.
     * Also creates a central identity if one does not already exist for this email.
     *
     * Requires X-Tenant-ID header.
     */
    public function register(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        // Upsert central identity.
        $centralUser = CentralUser::firstOrCreate(
            ['email' => $data['email']],
            ['name'  => $data['name'], 'password' => $data['password']],
        );

        // Create tenant-scoped user.
        $tenantUser = TenantUser::create([
            'central_user_id' => $centralUser->id,
            'name'            => $data['name'],
            'email'           => $data['email'],
            'password'        => $data['password'],
        ]);

        Auth::login($tenantUser);
        $request->session()->regenerate();

        $token = $tenantUser->createToken('api')->plainTextToken;

        return response()->json([
            'user'  => $tenantUser,
            'token' => $token,
        ], 201);
    }

    public function user(Request $request): JsonResponse
    {
        return response()->json($request->user());
    }
}
