<?php

namespace App\Http\Middleware;

use App\Models\Company;
use App\Services\TenantDatabase;
use Closure;
use RuntimeException;
use Throwable;

class ResolveTenant
{
    public function handle($request, Closure $next)
    {
        if ($request->is('superadmin') || $request->is('superadmin/*')) { app(TenantDatabase::class)->deactivate(); return $next($request); }
        $code=(string)$request->session()->get('tenant_company_code','');
        if ($code==='') { app(TenantDatabase::class)->deactivate(); return $next($request); }
        $company=Company::where('company_code',$code)->where('is_active',true)->first();
        if (!$company) { $request->session()->forget('tenant_company_code'); return redirect()->route('login')->withErrors(['company_code'=>'Company access is unavailable.']); }
        try { app(TenantDatabase::class)->activate($company); }
        catch (Throwable $error) { report($error); $request->session()->forget('tenant_company_code'); return redirect()->route('login')->withErrors(['company_code'=>'The company database is unavailable. Contact the system owner.']); }
        return $next($request);
    }
}
