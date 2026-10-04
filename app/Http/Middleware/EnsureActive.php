<?php
namespace App\Http\Middleware;
use Closure;
use Illuminate\Support\Facades\Auth;
class EnsureActive
{
    public function handle($request, Closure $next)
    {
        if (!$request->user()->is_active) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            return redirect()->route('login')->withErrors(['username' => 'Your account has been disabled. Contact the Super Admin.']);
        }
        return $next($request);
    }
}
