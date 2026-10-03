@extends('layouts.app')
@section('title', 'Users & company access')
@section('eyebrow', 'Super Admin · Administration')
@section('description', 'Create accounts and assign one or more companies to each manager.')
@section('actions')<a class="btn" href="{{ route('users.create') }}">+ Add user</a>@endsection
@section('content')<div class="card"><div class="table-wrap"><table><thead><tr><th>User</th><th>Role</th><th>Company access</th><th>Status</th><th></th></tr></thead><tbody>@foreach($users as $account)<tr><td><strong>{{ $account->name }}</strong><span class="sub">{{ $account->email }}</span></td><td><span class="badge">{{ $account->isAdmin() ? 'Super Admin' : 'Manager' }}</span></td><td class="wrap">{{ $account->isAdmin() ? 'All companies' : ($account->companies->pluck('name')->join(', ') ?: 'No companies assigned') }}</td><td><span class="badge {{ $account->is_active ? 'active' : 'inactive' }}">{{ $account->is_active ? 'Active' : 'Disabled' }}</span></td><td><a class="btn secondary small" href="{{ route('users.edit', $account) }}">Manage</a></td></tr>@endforeach</tbody></table></div>@include('partials.pagination', ['items' => $users])</div>@endsection
