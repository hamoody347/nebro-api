<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetPermissionsTeamId
{
    public function handle(Request $request, Closure $next): Response
    {
        if (tenancy()->initialized()) {
            setPermissionsTeamId(tenancy()->tenant->id);
        }

        return $next($request);
    }
}
