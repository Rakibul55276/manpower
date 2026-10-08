<?php
namespace App\Http\Middleware;

use Closure;

class EnsureSaasSubscription
{
    public function handle($request, Closure $next)
    {
        if (!config('saas.saas_voucher_enabled') || !$request->user() || $request->user()->isSuperAdmin()) return $next($request);
        $company = $request->user()->company;
        if ($company && $company->hasApplicationAccess()) return $next($request);
        if ($request->routeIs('subscription.*') || $request->routeIs('logout') || $request->routeIs('profile*')) return $next($request);
        return redirect()->route('subscription.show')->withErrors(['subscription'=>'Company subscription is '.$company->subscriptionState().'. Redeem a valid voucher to continue.']);
    }
}
