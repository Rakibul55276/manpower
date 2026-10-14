@extends('layouts.app')
@section('title', $account->exists ? 'Manage account' : 'Add account')
@section('content')
<form method="POST" action="{{ $account->exists ? route($userRoutePrefix.'.update', $account) : route($userRoutePrefix.'.store') }}">
    @csrf
    @if($account->exists) @method('PUT') @endif
    <div class="columns">
        <div class="card">
            <div class="card-head"><h2>Account details</h2></div>
            <div class="card-body">
                <div class="form-grid">
                    <div class="field"><label for="name">Name *</label><input id="name" name="name" required maxlength="150" value="{{ old('name', $account->name) }}"></div>
                    <div class="field"><label for="username">Username *</label><input id="username" name="username" required maxlength="50" value="{{ old('username', $account->username) }}" autocomplete="username"><p class="help">Letters, numbers, dashes, and underscores only.</p></div>
                    @if(auth()->user()->isSuperAdmin())<div class="field"><label for="role">Role *</label><select id="role" name="role">@foreach(['super_admin' => 'System Owner', 'admin' => 'Company Admin', 'manager' => 'Manager / Approver', 'issuer' => 'Issuer / Operator'] as $role => $label)<option value="{{ $role }}" {{ old('role', $account->role) === $role ? 'selected' : '' }}>{{ $label }}</option>@endforeach</select></div>@else<input type="hidden" id="role" name="role" value="{{ old('role',$account->role?:'issuer') }}">@endif
                    <div class="field"><label for="is_active">Account status *</label><select id="is_active" name="is_active"><option value="1" {{ old('is_active', $account->is_active) ? 'selected' : '' }}>Active</option><option value="0" {{ !old('is_active', $account->is_active) ? 'selected' : '' }}>Disabled</option></select></div>
                    <div class="field"><label for="password">{{ $account->exists ? 'Reset password (optional)' : 'Password *' }}</label><input id="password" name="password" type="password" minlength="12" autocomplete="new-password" {{ $account->exists ? '' : 'required' }}><p class="help">At least 12 characters with upper/lowercase and a number or symbol. {{ $account->exists ? 'Leave blank to keep the current password.' : '' }}</p></div>
                    <div class="field"><label for="password_confirmation">Confirm password</label><input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" {{ $account->exists ? '' : 'required' }}></div>
                </div>
            </div>
        </div>
        <div class="card">
            <div class="card-head"><h2>Access scope</h2><span class="badge">Per module</span></div>
            <div class="card-body">
                <p class="description" style="margin-bottom:18px">The Company Admin has full access to both modules. Other accounts can have a different role and branch in each module.</p>
                <div class="form-grid">
                    @if(auth()->user()->isSuperAdmin())
                    <div class="field full"><label for="company_id">Company</label><select id="company_id" name="company_id"><option value="">Global (System Owner only)</option>@foreach($companies as $company)<option value="{{ $company->id }}" {{ old('company_id',$account->company_id)==$company->id?'selected':'' }}>{{ $company->name }}{{ !$company->is_active?' (inactive)':'' }}</option>@endforeach</select></div>
                    @else
                    <input id="company_id" name="company_id" type="hidden" value="{{ auth()->user()->company_id }}">
                    <div class="field full"><label>Company</label><input value="{{ optional(auth()->user()->company)->name }}" disabled><p class="help">Users created here are always assigned to your company.</p></div>
                    @endif
                    @php($mp=$account->moduleAccesses->firstWhere('module','manpower')) @php($ss=$account->moduleAccesses->firstWhere('module','safety-shop'))
                    <div id="module-access-fields" class="field full" style="display:contents">
                    @foreach(['manpower'=>['Manpower',$mp],'safety_shop'=>['Safety Shop',$ss]] as $key=>$module)
                    <div class="field full" style="border:1px solid var(--border);padding:14px;border-radius:10px"><label><input type="checkbox" name="{{ $key }}_enabled" value="1" {{ old($key.'_enabled',$module[1]||(!$account->exists&&in_array($account->role,['manager','issuer'],true)))?'checked':'' }}> Enable {{ $module[0] }}</label><div class="form-grid" style="margin-top:12px"><div class="field"><label>Role</label><select name="{{ $key }}_role"><option value="issuer" {{ old($key.'_role',optional($module[1])->role)==='issuer'?'selected':'' }}>Issuer / Operator</option><option value="manager" {{ old($key.'_role',optional($module[1])->role)==='manager'?'selected':'' }}>Manager / Approver</option></select></div><div class="field"><label>Branch</label><select class="module-branch" name="{{ $key }}_branch_id"><option value="">Choose a branch</option>@foreach($branches as $branch)<option value="{{ $branch->id }}" data-company="{{ $branch->company_id }}" {{ old($key.'_branch_id',optional($module[1])->branch_id?:$account->branch_id)==$branch->id?'selected':'' }}>{{ auth()->user()->isSuperAdmin()?$branch->company->name.' · ':'' }}{{ $branch->name }}</option>@endforeach</select></div></div></div>
                    @endforeach
                    </div>
                </div>
                <p class="help">Each module branch can have one Manager and multiple Issuers.</p>
            </div>
        </div>
    </div>
    <div class="form-footer"><a href="{{ route($userRoutePrefix.'.index') }}" class="btn secondary">Cancel</a><button class="btn">Save user account →</button></div>
</form>
<script>document.addEventListener('DOMContentLoaded',function(){var company=document.getElementById('company_id'),role=document.getElementById('role'),scope=document.getElementById('module-access-fields'),branches=Array.from(document.querySelectorAll('.module-branch'));if(!company||!role)return;function sync(){var operational=role.value==='manager'||role.value==='issuer';if(company.tagName==='SELECT')company.disabled=role.value==='super_admin';if(scope)scope.style.display=operational?'contents':'none';branches.forEach(function(branch){branch.disabled=!operational;Array.from(branch.options).forEach(function(o){if(o.value)o.hidden=!!company.value&&o.dataset.company!==company.value;});if(branch.selectedOptions[0]&&branch.selectedOptions[0].hidden)branch.value='';});}if(company.tagName==='SELECT')company.addEventListener('change',sync);role.addEventListener('change',sync);sync();});</script>
@endsection
