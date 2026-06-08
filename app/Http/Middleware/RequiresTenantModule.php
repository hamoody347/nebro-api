<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequiresTenantModule
{
    public function handle(Request $request, Closure $next, string $submodule): Response
    {
        if (! tenancy()->initialized() || ! tenancy()->tenant->hasSubmodule($submodule)) {
            abort(403, "Module [{$submodule}] is not available for this tenant.");
        }

        return $next($request);
    }
}
