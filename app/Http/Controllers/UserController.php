<?php
namespace App\Http\Controllers;
use App\Models\User;
use App\Models\Company;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
class UserController extends Controller
{
    public function index() { return view('users.index', ['users' => User::with('companies')->orderBy('name')->paginate(15)]); }
    public function create() { return view('users.form', ['account' => new User(['role' => 'manager', 'is_active' => true]), 'companies' => Company::orderBy('name')->get()]); }
    public function edit(User $user) { $user->load('companies'); return view('users.form', ['account' => $user, 'companies' => Company::orderBy('name')->get()]); }
    public function store(Request $request) { return $this->save($request, new User); }
    public function update(Request $request, User $user) { return $this->save($request, $user); }
    private function save(Request $request, User $user)
    {
        $data = $request->validate([
            'name' => 'required|string|max:150', 'email' => ['required', 'email', 'max:150', Rule::unique('users')->ignore($user->id)],
            'password' => ($user->exists ? 'nullable' : 'required').'|string|min:10|confirmed',
            'role' => 'required|in:super_admin,manager', 'is_active' => 'required|boolean',
            'companies' => 'nullable|array', 'companies.*' => 'integer|distinct|exists:companies,id',
        ]);
        if ($user->id === auth()->id() && ($data['role'] !== 'super_admin' || !$data['is_active'])) { return back()->withErrors(['role' => 'You cannot disable or demote your own account.'])->withInput($request->except('password', 'password_confirmation')); }
        if ($data['role'] === 'manager' && empty($data['companies'])) { return back()->withErrors(['companies' => 'Assign at least one company to a manager.'])->withInput($request->except('password', 'password_confirmation')); }
        $companies = $data['companies'] ?? []; unset($data['companies']);
        if (!empty($data['password'])) { $data['password'] = Hash::make($data['password']); $data['remember_token'] = null; } else { unset($data['password']); }
        DB::transaction(function () use ($user, $data, $companies) {
            $user->fill($data)->save(); $user->companies()->sync($data['role'] === 'manager' ? $companies : []);
            ActivityLog::record('Saved account', $user->name.' · '.$user->role);
        });
        return redirect()->route('users.index')->with('success', 'User account saved.');
    }
}
