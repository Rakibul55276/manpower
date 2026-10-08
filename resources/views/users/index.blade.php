@extends('layouts.app')
@section('title', 'User accounts')
@section('actions')<a class="btn" href="{{ route('users.create') }}">Add user →</a>@endsection
@section('content')
<div class="card">
    <div class="table-wrap">
        <table>
            <thead><tr><th>User</th><th>Role</th><th>Company access</th><th>Status</th><th></th></tr></thead>
            <tbody>
            @foreach($users as $account)
                <tr>
                    <td><strong>{{ $account->name }}</strong><span class="sub">{{ $account->username }}</span></td>
                    <td><span class="badge">{{ $account->isSuperAdmin() ? 'Super Admin' : ($account->isCompanyAdmin() ? 'Company Admin' : 'Branch Manager') }}</span></td>
                    <td class="wrap">{{ $account->isSuperAdmin() ? 'All companies and branches' : (optional($account->company)->name ?: 'No company assigned') }}@if($account->branch)<span class="sub">{{ $account->branch->name }}</span>@endif</td>
                    <td><span class="badge {{ $account->is_active ? 'active' : 'inactive' }}">{{ $account->is_active ? 'Active' : 'Disabled' }}</span></td>
                    <td><a class="btn secondary small" href="{{ route('users.edit', $account) }}">Manage</a></td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    @include('partials.pagination', ['items' => $users])
</div>
@endsection
