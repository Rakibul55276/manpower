<?php

namespace App\Http\Middleware;

use Closure;

class UserAdministrator
{
    public function handle($request, Closure $next)
    {
        abort_unless($request->user()->isSuperAdmin() || $request->user()->isCompanyAdmin(), 403);

        return $next($request);
    }
}
