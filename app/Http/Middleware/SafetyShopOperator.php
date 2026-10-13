<?php

namespace App\Http\Middleware;

use Closure;

class SafetyShopOperator
{
    public function handle($request, Closure $next)
    {
        abort_unless($request->user()->canOperateSafetyShop(), 403);

        return $next($request);
    }
}
