@extends('layouts.app')
@section('title','User accounts')
@section('description','Platform users and company teams grouped by access boundary.')
@section('actions')<a class="btn" href="{{ route('users.create') }}">Add user →</a>@endsection
@section('content')
<div class="card">
<div class="card-head"><div><h2>Platform superadmins</h2><span class="sub">Unrestricted system configuration and all-company access</span></div><span class="badge">{{ $platformUsers->count() }} users</span></div>
@include('users.partials.account-table',['accounts'=>$platformUsers,'empty'=>'No platform superadmin accounts found.'])
</div>

<div class="grid-2">
@forelse($companies as $company)
<div class="card">
<div class="card-head"><div><h2>{{ $company->name }}</h2><span class="sub">{{ $company->company_code ?: 'No company code' }} · {{ $company->branches->count() }} {{ Str::plural('branch',$company->branches->count()) }}</span></div><span class="badge {{ $company->is_active ? 'active' : 'inactive' }}">{{ $company->users->count() }} {{ Str::plural('user',$company->users->count()) }}</span></div>
@include('users.partials.account-table',['accounts'=>$company->users,'empty'=>'No administrator or manager assigned.'])
<div class="card-body" style="padding-top:12px;padding-bottom:12px;border-top:1px solid var(--line)"><div class="actions"><a class="btn secondary small" href="{{ route('companies.branches.index',$company) }}">Manage branches</a><a class="btn secondary small" href="{{ route('users.create') }}">Add company user</a></div></div>
</div>
@empty
<div class="card full"><div class="empty"><strong>No companies configured</strong><p>Create a company before assigning administrators and branch managers.</p><a class="btn" href="{{ route('companies.create') }}">Add company</a></div></div>
@endforelse
</div>

@if($unassignedUsers->isNotEmpty())
<div class="card">
<div class="card-head"><div><h2>Unassigned company users</h2><span class="sub">Assign these accounts before they can use company modules</span></div><span class="badge inactive">{{ $unassignedUsers->count() }} users</span></div>
@include('users.partials.account-table',['accounts'=>$unassignedUsers,'empty'=>'No unassigned users.'])
</div>
@endif
@endsection
