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
                    <div class="field"><label for="role">Role *</label><select id="role" name="role">@foreach(auth()->user()->isSuperAdmin() ? ['admin' => 'Company Admin', 'super_admin' => 'System Owner'] : ['manager' => 'Manager (Approver)', 'issuer' => 'Issuer'] as $role => $label)<option value="{{ $role }}" {{ old('role', $account->role) === $role ? 'selected' : '' }}>{{ $label }}</option>@endforeach</select></div>
                    <div class="field"><label for="is_active">Account status *</label><select id="is_active" name="is_active"><option value="1" {{ old('is_active', $account->is_active) ? 'selected' : '' }}>Active</option><option value="0" {{ !old('is_active', $account->is_active) ? 'selected' : '' }}>Disabled</option></select></div>
                    <div class="field"><label for="password">{{ $account->exists ? 'Reset password (optional)' : 'Password *' }}</label><input id="password" name="password" type="password" minlength="12" autocomplete="new-password" {{ $account->exists ? '' : 'required' }}><p class="help">At least 12 characters with upper/lowercase and a number or symbol. {{ $account->exists ? 'Leave blank to keep the current password.' : '' }}</p></div>
                    <div class="field"><label for="password_confirmation">Confirm password</label><input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" {{ $account->exists ? '' : 'required' }}></div>
                </div>
            </div>
        </div>
        <div class="card">
            <div class="card-head"><h2>Access scope</h2><span class="badge">Single assignment</span></div>
            <div class="card-body">
                <p class="description" style="margin-bottom:18px">Company Admin controls the whole company. Each branch has one Manager for approvals and may have multiple Issuers for data entry. System Owner is platform-only.</p>
                <div class="form-grid">
                    @if(auth()->user()->isSuperAdmin())
                    <div class="field full"><label for="company_id">Company</label><select id="company_id" name="company_id"><option value="">Global (System Owner only)</option>@foreach($companies as $company)<option value="{{ $company->id }}" {{ old('company_id',$account->company_id)==$company->id?'selected':'' }}>{{ $company->name }}{{ !$company->is_active?' (inactive)':'' }}</option>@endforeach</select></div>
                    @else
                    <input id="company_id" name="company_id" type="hidden" value="{{ auth()->user()->company_id }}">
                    <div class="field full"><label>Company</label><input value="{{ optional(auth()->user()->company)->name }}" disabled><p class="help">Users created here are always assigned to your company.</p></div>
                    @endif
                    <div class="field full"><label for="branch_id">Branch (Manager and Issuer)</label><select id="branch_id" name="branch_id"><option value="">Choose a branch</option>@foreach($branches as $branch)<option value="{{ $branch->id }}" data-company="{{ $branch->company_id }}" {{ old('branch_id',$account->branch_id)==$branch->id?'selected':'' }}>{{ $branch->company->name }} · {{ $branch->name }}{{ !$branch->is_active?' (inactive)':'' }}</option>@endforeach</select></div>
                </div>
                <p class="help">A company can have one Company Admin, and each branch can have one Manager account.</p>
            </div>
        </div>
    </div>
    <div class="form-footer"><a href="{{ route($userRoutePrefix.'.index') }}" class="btn secondary">Cancel</a><button class="btn">Save user account →</button></div>
</form>
<script>document.addEventListener('DOMContentLoaded',function(){var company=document.getElementById('company_id'),branch=document.getElementById('branch_id'),role=document.getElementById('role');function sync(){var branchRole=role.value==='manager'||role.value==='issuer';if(company.tagName==='SELECT')company.disabled=role.value==='super_admin';branch.disabled=!branchRole;Array.from(branch.options).forEach(function(o){if(o.value)o.hidden=!!company.value&&o.dataset.company!==company.value;});if(branchRole&&branch.selectedOptions[0]&&branch.selectedOptions[0].hidden)branch.value='';}if(company.tagName==='SELECT')company.addEventListener('change',sync);role.addEventListener('change',sync);sync();});</script>
@endsection
