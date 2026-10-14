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
    public function index(Request $request)
    {
        $query=User::with(['company','branch','moduleAccesses.branch']);
        if($request->user()->isCompanyAdmin())$query->where('company_id',$request->user()->company_id)->whereIn('role',['manager','issuer']);
        if($search=trim((string)$request->input('search'))) $query->where(fn($q)=>$q->where('name','like','%'.$search.'%')->orWhere('username','like','%'.$search.'%')->orWhereHas('company',fn($company)=>$company->where('name','like','%'.$search.'%')));
        if(in_array($request->input('role'),['super_admin','admin','manager','issuer'],true))$query->where('role',$request->role);
        if($request->user()->isSuperAdmin() && $request->filled('company_id'))$query->where('company_id',(int)$request->company_id);
        $accounts=$query->orderByRaw("CASE role WHEN 'super_admin' THEN 0 WHEN 'admin' THEN 1 WHEN 'manager' THEN 2 WHEN 'issuer' THEN 3 ELSE 4 END")->orderBy('name')->paginate(25)->withQueryString();
        $userRoutePrefix=$this->routePrefix();
        return view('users.index', compact('accounts','userRoutePrefix'));
    }
    public function create() { return $this->form(new User(['role' => 'issuer', 'is_active' => true])); }
    public function edit(User $user) { $this->authorizeAccount($user); return $this->form($user->load(['company','branch','moduleAccesses'])); }
    private function form(User $account) { $account->loadMissing('moduleAccesses'); $companies=Company::when(auth()->user()->isCompanyAdmin(),fn($q)=>$q->whereKey(auth()->user()->company_id))->orderBy('name')->get(); $branches=Branch::with('company')->when(auth()->user()->isCompanyAdmin(),fn($q)=>$q->where('company_id',auth()->user()->company_id))->orderBy('name')->get(); $userRoutePrefix=$this->routePrefix(); return view('users.form', compact('account','companies','branches','userRoutePrefix')); }
    public function store(Request $request) { return $this->save($request, new User); }
    public function update(Request $request, User $user) { return $this->save($request, $user); }
    public function destroy(User $user)
    {
        $this->authorizeAccount($user);
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
        return redirect()->route($this->routePrefix().'.index')->with('success','User account removed.');
    }
    private function save(Request $request, User $user)
    {
        if($user->exists)$this->authorizeAccount($user);
        $moduleAssignments=[];
        $operationalRole=in_array($request->input('role'),['manager','issuer'],true);
        if($request->user()->isCompanyAdmin()) {
            if(!in_array($request->input('role','issuer'),['manager','issuer'],true))return back()->withErrors(['role'=>'Company administrators may create only Manager or Issuer accounts.'])->withInput();
            $operationalRole=true;
        }
        if($operationalRole) {
            foreach (['manpower','safety-shop'] as $module) {
                $key=str_replace('-','_',$module);
                if($request->boolean($key.'_enabled')) $moduleAssignments[$module]=['role'=>$request->input($key.'_role'),'branch_id'=>$request->input($key.'_branch_id')];
            }
            if(!$moduleAssignments && !$request->hasAny(['manpower_enabled','safety_shop_enabled','manpower_role','safety_shop_role'])) {
                foreach(['manpower','safety-shop'] as $module)$moduleAssignments[$module]=['role'=>$request->input('role','issuer'),'branch_id'=>$request->input('branch_id')];
            }
            if(!$moduleAssignments) return back()->withErrors(['module_access'=>'Assign at least one module.'])->withInput();
            $legacy=reset($moduleAssignments);
            $request->merge(['role'=>collect($moduleAssignments)->contains('role','manager')?'manager':'issuer','branch_id'=>$legacy['branch_id']]);
        }
        if($request->user()->isCompanyAdmin())$request->merge(['company_id'=>$request->user()->company_id]);
        $allowedRoles=$request->user()->isSuperAdmin()?'super_admin,admin,manager,issuer':'manager,issuer';
        $data = $request->validate([
            'name' => 'required|string|max:150',
            'username' => ['required', 'alpha_dash', 'max:50', Rule::unique('users')->ignore($user->id)],
            'password' => [($user->exists ? 'nullable' : 'required'), 'string', 'min:12', 'confirmed', 'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9@#$%^&*!?._-]).+$/'],
            'role' => 'required|in:'.$allowedRoles, 'is_active' => 'required|boolean',
            'company_id' => 'nullable|required_unless:role,super_admin|integer|exists:companies,id',
            'branch_id' => 'nullable|required_if:role,manager|required_if:role,issuer|integer|exists:branches,id',
            'manpower_role' => 'nullable|in:manager,issuer', 'manpower_branch_id' => 'nullable|integer|exists:branches,id',
            'safety_shop_role' => 'nullable|in:manager,issuer', 'safety_shop_branch_id' => 'nullable|integer|exists:branches,id',
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
            if ($data['role'] === 'manager' && !$moduleAssignments) {
                $otherManager=User::where('role','manager')->where('branch_id',$data['branch_id'])->where('id','<>',$user->id ?: 0)->exists();
                if($otherManager) return back()->withErrors(['branch_id'=>'This branch already has a manager account.'])->withInput($request->except('password','password_confirmation'));
            }
        }
        foreach($moduleAssignments as $module=>$assignment){
            $branch=Branch::whereKey($assignment['branch_id'])->where('company_id',$data['company_id'])->where('is_active',true)->first();
            if(!$branch)return back()->withErrors([str_replace('-','_',$module).'_branch_id'=>'Choose an active company branch.'])->withInput();
            if($assignment['role']==='manager' && DB::table('user_module_access')->where('module',$module)->where('branch_id',$branch->id)->where('role','manager')->where('user_id','<>',$user->id?:0)->exists()){
                $key=$request->hasAny(['manpower_enabled','safety_shop_enabled','manpower_role','safety_shop_role'])?str_replace('-','_',$module).'_branch_id':'branch_id';
                return back()->withErrors([$key=>'This module branch already has a manager.'])->withInput();
            }
        }
        $data['email'] = strtolower($data['username']).'@manpower.local';
        if (!empty($data['password'])) { $data['password'] = Hash::make($data['password']); $data['remember_token'] = null; } else { unset($data['password']); }
        DB::transaction(function () use ($user, $data, $moduleAssignments) {
            $user->fill($data)->save(); $user->companies()->detach();
            $user->moduleAccesses()->delete();
            if($moduleAssignments){
                foreach($moduleAssignments as $module=>$assignment)$user->moduleAccesses()->create(['company_id'=>$user->company_id,'module'=>$module,'branch_id'=>$assignment['branch_id'],'role'=>$assignment['role'],'is_active'=>$user->is_active]);
            }
            ActivityLog::record('Saved account', $user->name.' · '.$user->role);
        });
        return redirect()->route($this->routePrefix().'.index')->with('success', 'User account saved.');
    }

    private function authorizeAccount(User $user)
    {
        if(auth()->user()->isSuperAdmin())return;
        abort_unless(auth()->user()->isCompanyAdmin() && (int)$user->company_id===(int)auth()->user()->company_id && in_array($user->role,['manager','issuer'],true),403);
    }

    private function routePrefix() { return auth()->user()->isSuperAdmin() ? 'users' : 'company-users'; }
}
