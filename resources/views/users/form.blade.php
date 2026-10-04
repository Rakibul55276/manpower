@extends('layouts.app')
@section('title', $account->exists ? 'Manage account' : 'Add account')
@section('content')
<form method="POST" action="{{ $account->exists ? route('users.update', $account) : route('users.store') }}">
    @csrf
    @if($account->exists) @method('PUT') @endif
    <div class="columns">
        <div class="card">
            <div class="card-head"><h2>Account details</h2></div>
            <div class="card-body">
                <div class="form-grid">
                    <div class="field"><label for="name">Name *</label><input id="name" name="name" required maxlength="150" value="{{ old('name', $account->name) }}"></div>
                    <div class="field"><label for="username">Username *</label><input id="username" name="username" required maxlength="50" value="{{ old('username', $account->username) }}" autocomplete="username"><p class="help">Letters, numbers, dashes, and underscores only.</p></div>
                    <div class="field"><label for="role">Role *</label><select id="role" name="role">@foreach(['manager' => 'Manager', 'admin' => 'Admin (Approver)', 'super_admin' => 'Super Admin'] as $role => $label)<option value="{{ $role }}" {{ old('role', $account->role) === $role ? 'selected' : '' }}>{{ $label }}</option>@endforeach</select></div>
                    <div class="field"><label for="is_active">Account status *</label><select id="is_active" name="is_active"><option value="1" {{ old('is_active', $account->is_active) ? 'selected' : '' }}>Active</option><option value="0" {{ !old('is_active', $account->is_active) ? 'selected' : '' }}>Disabled</option></select></div>
                    <div class="field"><label for="password">{{ $account->exists ? 'Reset password (optional)' : 'Password *' }}</label><input id="password" name="password" type="password" minlength="10" autocomplete="new-password" {{ $account->exists ? '' : 'required' }}><p class="help">At least 10 characters. {{ $account->exists ? 'Leave blank to keep the current password.' : '' }}</p></div>
                    <div class="field"><label for="password_confirmation">Confirm password</label><input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" {{ $account->exists ? '' : 'required' }}></div>
                </div>
            </div>
        </div>
        <div class="card">
            <div class="card-head"><h2>Assigned companies</h2><span class="badge">Multiple allowed</span></div>
            <div class="card-body">
                <p class="description" style="margin-bottom:18px">Select every company this manager should access. Super Admin accounts automatically access all companies.</p>
                <div class="checkbox-list">@forelse($companies as $company)<label><input type="checkbox" name="companies[]" value="{{ $company->id }}" {{ in_array($company->id, old('companies', $account->exists ? $account->companies->pluck('id')->all() : [])) ? 'checked' : '' }}>{{ $company->name }}{{ !$company->is_active ? ' (inactive)' : '' }}</label>@empty<p class="help">Create a company before adding a manager.</p>@endforelse</div>
                <p class="help">Managers can maintain employee profiles and hours. The Super Admin controls approvals, salaries, users, and companies.</p>
            </div>
        </div>
    </div>
    <div class="form-footer"><a href="{{ route('users.index') }}" class="btn secondary">Cancel</a><button class="btn">Save user account →</button></div>
</form>
@endsection
