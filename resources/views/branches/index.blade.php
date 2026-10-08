@extends('layouts.app')
@section('title', $company->name.' branches')
@section('eyebrow','Organization settings')
@section('description','Manage operational branches under this client company.')
@section('actions')<a class="btn" href="{{ route('companies.branches.create',$company) }}">+ Add branch</a>@endsection
@section('content')
<div class="card"><div class="card-head"><h2>{{ $company->name }}</h2><a href="{{ route('companies.index') }}">← Companies</a></div><div class="table-wrap"><table><thead><tr><th>Branch</th><th>Location</th><th>Manager</th><th>Employees</th><th>Status</th><th>Actions</th></tr></thead><tbody>
@forelse($branches as $branch)<tr><td><strong>{{ $branch->name }}</strong><span class="sub">{{ $branch->code }}</span></td><td>{{ $branch->location }}</td><td>{{ $branch->managers_count }}</td><td>{{ $branch->employees_count }}</td><td><span class="badge {{ $branch->is_active?'active':'inactive' }}">{{ $branch->is_active?'Active':'Inactive' }}</span></td><td><div class="actions"><a class="btn secondary small" href="{{ route('companies.branches.edit',[$company,$branch]) }}">Edit</a><form method="POST" action="{{ route('companies.branches.destroy',[$company,$branch]) }}" data-confirm="Delete this branch?">@csrf @method('DELETE')<button class="btn danger small">Delete</button></form></div></td></tr>
@empty<tr><td colspan="6"><div class="empty">No branches created.</div></td></tr>@endforelse
</tbody></table></div>@include('partials.pagination',['items'=>$branches])</div>
@endsection
