<?php

namespace App\Http\Middleware;

use App\Providers\RouteServiceProvider;
use Closure;
use Illuminate\Support\Facades\Auth;

class RedirectIfAuthenticated
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string[]|null  ...$guards
     * @return mixed
     */
    public function handle($request, Closure $next, ...$guards)
    {
        $guards = empty($guards) ? [null] : $guards;

        foreach ($guards as $guard) {
            if (Auth::guard($guard)->check()) {
                $requestedModule=$request->route('module');
                $activeModule=$request->session()->get('active_module');
                if ($requestedModule && $activeModule && $requestedModule !== $activeModule) {
                    Auth::guard($guard)->logout();
                    $request->session()->invalidate();
                    $request->session()->regenerateToken();
                    return $next($request);
                }
                $module=$requestedModule ?: ($activeModule ?: 'manpower');
                return redirect()->route($module==='safety-shop'?'safety-shop.index':'manpower.index');
            }
        }

        return $next($request);
    }
}
