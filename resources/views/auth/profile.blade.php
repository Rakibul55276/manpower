@extends('layouts.app')
@section('title', 'My profile')
@section('content')
<div class="columns">
    <div class="card">
        <div class="card-head"><h2>Change password</h2></div>
        <div class="card-body">
            <form method="POST" action="{{ route('profile.password') }}">
                @csrf @method('PUT')
                <div class="form-grid">
                    <div class="field full"><label for="current_password">Current password</label><input type="password" id="current_password" name="current_password" required autocomplete="current-password"></div>
                    <div class="field"><label for="password">New password</label><input type="password" id="password" name="password" minlength="10" required autocomplete="new-password"><p class="help">At least 10 characters.</p></div>
                    <div class="field"><label for="password_confirmation">Confirm password</label><input type="password" id="password_confirmation" name="password_confirmation" required autocomplete="new-password"></div>
                </div>
                <div class="form-footer"><button class="btn">Update password</button></div>
            </form>
        </div>
    </div>
    <div class="card">
        <div class="card-head"><h2>Account details</h2></div>
        <div class="card-body">
            <dl class="detail-list">
                <div><dt>Name</dt><dd>{{ auth()->user()->name }}</dd></div>
                <div><dt>Role</dt><dd>{{ auth()->user()->role === 'super_admin' ? 'Super Admin' : (auth()->user()->role === 'admin' ? 'Admin Approver' : 'Manager') }}</dd></div>
                <div class="full"><dt>Username</dt><dd>{{ auth()->user()->username }}</dd></div>
                <div class="full"><dt>Company access</dt><dd>{{ auth()->user()->isSuperAdmin() ? 'All companies' : optional(auth()->user()->company)->name }}</dd></div>
                <div class="full"><dt>Branch access</dt><dd>{{ auth()->user()->isSuperAdmin() ? 'All branches' : (auth()->user()->isCompanyAdmin() ? 'All company branches' : optional(auth()->user()->branch)->name) }}</dd></div>
            </dl>
        </div>
    </div>
</div>
@endsection
