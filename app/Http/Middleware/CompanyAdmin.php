<?php

namespace App\Http\Middleware;

use Closure;

class CompanyAdmin
{
    public function handle($request, Closure $next)
    {
        abort_unless($request->user()->isCompanyAdmin(), 403);

        return $next($request);
    }
}
