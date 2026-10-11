<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SuperAdminAuthController extends Controller
{
    public function form()
    {
        if (Auth::check()) {
            return Auth::user()->isSuperAdmin() ? redirect()->route('companies.index') : abort(403);
        }

        return view('auth.superadmin-login');
    }

    public function login(Request $request)
    {
        $data=$request->validate(['username'=>'required|string','password'=>'required|string']);
        if(!Auth::attempt(['username'=>$data['username'],'password'=>$data['password'],'role'=>'super_admin','is_active'=>true])) {
            return back()->withErrors(['username'=>'Invalid system-owner credentials.'])->withInput($request->only('username'));
        }
        $request->session()->regenerate();
        ActivityLog::record('Signed in to superadmin portal',$request->user()->name);
        return redirect()->intended(route('companies.index'));
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('superadmin.login');
    }
}
