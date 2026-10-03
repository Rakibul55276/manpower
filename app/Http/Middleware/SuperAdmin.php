<?php
namespace App\Http\Middleware;
use Closure;
class SuperAdmin
{
    public function handle($request, Closure $next)
    {
        abort_unless($request->user()->role === 'super_admin', 403);
        return $next($request);
    }
}
