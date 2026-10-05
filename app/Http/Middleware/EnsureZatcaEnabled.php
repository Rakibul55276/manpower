<?php

namespace App\Http\Middleware;

use Closure;

class EnsureZatcaEnabled
{
    public function handle($request, Closure $next)
    {
        abort_unless(config('zatca.enabled'), 404);

        return $next($request);
    }
}
