<?php
namespace App\Http\Controllers;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\Company;
use App\Services\TenantDatabase;
use RuntimeException;
use Throwable;
class AuthController extends Controller
{
    public function form() { return view('auth.login'); }
    public function login(Request $request)
    {
        $data = $request->validate(['company_code'=>['required','string','max:30','regex:/^[A-Za-z0-9][A-Za-z0-9_-]+$/'],'username' => 'required|string', 'password' => 'required|string']);
        $code=strtoupper($data['company_code']);
        $company=Company::where('company_code',$code)->where('is_active',true)->first();
        if(!$company) return back()->withErrors(['company_code'=>'The company code is invalid or access is disabled.'])->withInput($request->only('company_code','username'));
        try { app(TenantDatabase::class)->activate($company); }
        catch(Throwable $error){report($error);app(TenantDatabase::class)->deactivate();return back()->withErrors(['company_code'=>'The company database is unavailable. Contact the system owner.'])->withInput($request->only('company_code','username'));}
        if (!Auth::attempt(['username'=>$data['username'],'password'=>$data['password'],'is_active'=>true,'company_id'=>$company->id])) {
            app(TenantDatabase::class)->deactivate();
            return back()->withErrors(['username' => 'The username or password is incorrect, or the account is disabled.'])->withInput($request->only('company_code','username'));
        }
        $request->session()->regenerate();
        $request->session()->put('tenant_company_code',$code);
        if ($request->user()->isSuperAdmin()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            return back()->withErrors(['username'=>'System owners must use the Superadmin portal.'])->withInput($request->only('username'));
        }
        ActivityLog::record('Signed in', $request->user()->name);
        $destination = $request->user()->isSuperAdmin() ? route('superadmin.index') : route('dashboard');
        return redirect()->intended($destination);
    }
    public function logout(Request $request)
    {
        Auth::logout(); $request->session()->invalidate(); $request->session()->regenerateToken();
        return redirect()->route('login');
    }
    public function profile() { return view('auth.profile'); }
    public function password(Request $request)
    {
        $data = $request->validate(['current_password' => 'required', 'password' => ['required', 'string', 'min:12', 'confirmed', 'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9@#$%^&*!?._-]).+$/']]);
        if (!Hash::check($data['current_password'], $request->user()->password)) { return back()->withErrors(['current_password' => 'Current password is incorrect.']); }
        $request->user()->update(['password' => Hash::make($data['password']), 'remember_token' => null]);
        $request->session()->regenerate();
        ActivityLog::record('Changed password', $request->user()->name);
        return back()->with('success', 'Password updated.');
    }
}
