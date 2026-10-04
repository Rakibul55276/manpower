<?php
namespace App\Http\Middleware;
use Closure;
class Approver
{
    public function handle($request, Closure $next)
    {
        abort_unless(in_array($request->user()->role, ['admin', 'super_admin'], true), 403);
        return $next($request);
    }
}
