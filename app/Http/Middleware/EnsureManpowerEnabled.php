<?php

namespace App\Http\Middleware;

use Closure;

class EnsureManpowerEnabled
{
    public function handle($request, Closure $next)
    {
        abort_unless((bool) config('manpower.enabled'), 404);

        return $next($request);
    }
}
