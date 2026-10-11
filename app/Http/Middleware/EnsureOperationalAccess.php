<?php

namespace App\Http\Middleware;

use Closure;

class EnsureOperationalAccess
{
    public function handle($request, Closure $next)
    {
        if ($request->user()->isSuperAdmin() && $request->routeIs('dashboard')) {
            return redirect()->route('superadmin.index');
        }
        abort_if($request->user()->isSuperAdmin(), 403, 'System owners manage the platform and cannot access company operational data.');

        return $next($request);
    }
}
