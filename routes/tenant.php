<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Tenant Routes
|--------------------------------------------------------------------------
|
| These routes are automatically loaded by TenancyServiceProvider with the
| [api, tenant, auth:sanctum, set_team] middleware stack and the /api/v1
| prefix applied. Every route here requires:
|   - A valid X-Tenant-ID header (tenant context)
|   - An authenticated user (Sanctum cookie or Bearer token)
|   - Spatie team context set to the current tenant
|
*/

Route::get('ping', fn () => response()->json(['tenant' => tenant('id')]));
