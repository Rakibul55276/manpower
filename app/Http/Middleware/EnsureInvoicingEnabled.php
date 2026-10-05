<?php

namespace App\Http\Middleware;

use Closure;

class EnsureInvoicingEnabled
{
    public function handle($request, Closure $next)
    {
        abort_unless(config('invoicing.enabled'), 404);

        return $next($request);
    }
}
