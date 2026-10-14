<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

$legacyManpowerRedirect = function (Request $request, string $section, ?string $path = null) {
    $target = '/manpower/'.$section.($path !== null && $path !== '' ? '/'.$path : '');
    if ($request->getQueryString()) $target .= '?'.$request->getQueryString();

    return redirect(url(ltrim($target, '/')), 308);
};

Route::middleware(['auth', 'active', 'auth.session', 'saas.subscription', 'operational', \App\Http\Middleware\EnsureManpowerEnabled::class])
    ->group(function () use ($legacyManpowerRedirect) {
        foreach (['employees', 'own-employees', 'timesheets', 'attendance', 'payrolls', 'salaries', 'employee-photos'] as $section) {
            Route::any('/'.$section.'/{path?}', function (Request $request, ?string $path = null) use ($legacyManpowerRedirect, $section) {
                return $legacyManpowerRedirect($request, $section, $path);
            })->where('path', '.*');
        }
        Route::any('/company/designations/{path?}', function (Request $request, ?string $path = null) use ($legacyManpowerRedirect) {
            return $legacyManpowerRedirect($request, 'company/designations', $path);
        })->where('path', '.*');
    });
