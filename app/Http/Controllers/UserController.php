<?php
namespace App\Http\Controllers;
use App\Models\User;
use App\Models\Company;
use App\Models\Branch;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Database\QueryException;
class UserController extends Controller
{
    public function index()
    {
        $companies=Company::with(['branches'=>fn($q)=>$q->orderBy('name'),'users'=>fn($q)=>$q->with('branch')->orderByRaw("CASE role WHEN 'admin' THEN 0 ELSE 1 END")->orderBy('name')])->orderBy('name')->get();
        $platformUsers=User::where('role','super_admin')->orderBy('name')->get();
        $unassignedUsers=User::where('role','<>','super_admin')->whereNull('company_id')->with('branch')->orderBy('name')->get();
        return view('users.index', compact('companies','platformUsers','unassignedUsers'));
    }
    public function create() { return $this->form(new User(['role' => 'manager', 'is_active' => true])); }
    public function edit(User $user) { return $this->form($user->load(['company','branch'])); }
    private function form(User $account) { return view('users.form', ['account'=>$account, 'companies'=>Company::orderBy('name')->get(), 'branches'=>Branch::with('company')->orderBy('name')->get()]); }
    public function store(Request $request) { return $this->save($request, new User); }
    public function update(Request $request, User $user) { return $this->save($request, $user); }
    public function destroy(User $user)
    {
        if ($user->id === auth()->id()) return back()->withErrors(['user'=>'You cannot remove your own account.']);
        if ($user->isSuperAdmin() && User::where('role','super_admin')->where('is_active',true)->count() <= 1) return back()->withErrors(['user'=>'The last active superadmin cannot be removed.']);
        $name=$user->name; $role=$user->role;
        try {
            DB::transaction(function () use ($user,$name,$role) {
                $user->companies()->detach();
                $user->delete();
                ActivityLog::record('Removed account', $name.' · '.$role);
            });
        } catch (QueryException $e) {
            return back()->withErrors(['user'=>'This account is referenced by business or audit history and cannot be deleted. Disable it instead.']);
        }
        return redirect()->route('users.index')->with('success','User account removed.');
    }
    private function save(Request $request, User $user)
    {
        $data = $request->validate([
            'name' => 'required|string|max:150',
            'username' => ['required', 'alpha_dash', 'max:50', Rule::unique('users')->ignore($user->id)],
            'password' => [($user->exists ? 'nullable' : 'required'), 'string', 'min:12', 'confirmed', 'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9@#$%^&*!?._-]).+$/'],
            'role' => 'required|in:super_admin,admin,manager', 'is_active' => 'required|boolean',
            'company_id' => 'nullable|required_unless:role,super_admin|integer|exists:companies,id',
            'branch_id' => 'nullable|required_if:role,manager|integer|exists:branches,id',
        ]);
        if ($user->id === auth()->id() && ($data['role'] !== 'super_admin' || !$data['is_active'])) { return back()->withErrors(['role' => 'You cannot disable or demote your own account.'])->withInput($request->except('password', 'password_confirmation')); }
        if ($data['role'] === 'super_admin') { $data['company_id']=null; $data['branch_id']=null; }
        elseif ($data['role'] === 'admin') {
            $data['branch_id']=null;
            $otherAdmin=User::where('role','admin')->where('company_id',$data['company_id'])->where('id','<>',$user->id ?: 0)->exists();
            if($otherAdmin) return back()->withErrors(['company_id'=>'This company already has an administrator.'])->withInput($request->except('password','password_confirmation'));
        } else {
            $branch=Branch::whereKey($data['branch_id'])->where('company_id',$data['company_id'])->where('is_active',true)->first();
            if(!$branch) return back()->withErrors(['branch_id'=>'Choose an active branch belonging to the selected company.'])->withInput($request->except('password','password_confirmation'));
            $otherManager=User::where('role','manager')->where('branch_id',$data['branch_id'])->where('id','<>',$user->id ?: 0)->exists();
            if($otherManager) return back()->withErrors(['branch_id'=>'This branch already has a manager account.'])->withInput($request->except('password','password_confirmation'));
        }
        $data['email'] = strtolower($data['username']).'@manpower.local';
        if (!empty($data['password'])) { $data['password'] = Hash::make($data['password']); $data['remember_token'] = null; } else { unset($data['password']); }
        DB::transaction(function () use ($user, $data) {
            $user->fill($data)->save(); $user->companies()->detach();
            ActivityLog::record('Saved account', $user->name.' · '.$user->role);
        });
        return redirect()->route('users.index')->with('success', 'User account saved.');
    }
}
