<?php
namespace App\Http\Middleware;
use Closure;
class EnsureSafetyShopEnabled
{
    public function handle($request, Closure $next)
    {
        abort_unless(config('safety_shop.enabled'), 404);
        return $next($request);
    }
}
