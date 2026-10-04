<?php
namespace App\Http\Controllers;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
class AuthController extends Controller
{
    public function form() { return view('auth.login'); }
    public function login(Request $request)
    {
        $data = $request->validate(['username' => 'required|string', 'password' => 'required|string']);
        if (!Auth::attempt(array_merge($data, ['is_active' => true]))) {
            return back()->withErrors(['username' => 'The username or password is incorrect, or the account is disabled.'])->withInput($request->only('username'));
        }
        $request->session()->regenerate();
        ActivityLog::record('Signed in', $request->user()->name);
        return redirect()->intended(route('dashboard'));
    }
    public function logout(Request $request)
    {
        Auth::logout(); $request->session()->invalidate(); $request->session()->regenerateToken();
        return redirect()->route('login');
    }
    public function profile() { return view('auth.profile'); }
    public function password(Request $request)
    {
        $data = $request->validate(['current_password' => 'required', 'password' => 'required|string|min:10|confirmed']);
        if (!Hash::check($data['current_password'], $request->user()->password)) { return back()->withErrors(['current_password' => 'Current password is incorrect.']); }
        $request->user()->update(['password' => Hash::make($data['password']), 'remember_token' => null]);
        $request->session()->regenerate();
        ActivityLog::record('Changed password', $request->user()->name);
        return back()->with('success', 'Password updated.');
    }
}
