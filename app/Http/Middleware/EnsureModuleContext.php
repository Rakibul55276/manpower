<?php

namespace App\Http\Middleware;

use Closure;

class EnsureModuleContext
{
    public function handle($request, Closure $next, string $module)
    {
        abort_unless(in_array($module, ['manpower', 'safety-shop'], true), 404);
        abort_unless($request->user() && $request->user()->canAccessModule($module), 403, 'Your account is not assigned to this module.');
        $active = $request->session()->get('active_module');
        if ($active === null) {
            $request->session()->put('active_module', $module);
        } elseif ($active !== $module) {
            abort(403, 'Sign out and use the '.$module.' login page to access this module.');
        }

        return $next($request);
    }
}
