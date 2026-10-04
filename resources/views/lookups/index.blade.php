@extends('layouts.app')
@section('title', ucfirst($type))
@section('eyebrow', 'Organization settings')
@section('description', $type === 'companies' ? 'View company master data and locations.' : 'View workforce designations.')
@if($canManage)
@section('actions')
<a class="btn" href="{{ route($type.'.create') }}">+ Add {{ $type === 'companies' ? 'company' : 'designation' }}</a>
@endsection
@endif
@section('content')
<div class="card"><div class="card-head"><h2>{{ ucfirst($type) }} directory</h2><span class="badge">{{ $items->total() }} records</span></div><div class="table-wrap"><table><thead><tr><th>Name</th>@if($type === 'companies')<th>Location</th><th>Contact</th>@endif<th>Employees</th><th>Status</th>@if($canManage)<th>Actions</th>@endif</tr></thead><tbody>
@forelse($items as $item)<tr><td><strong>{{ $item->name }}</strong>@if($type === 'companies' && $item->registration_number)<span class="sub">CR: {{ $item->registration_number }}</span>@endif</td>@if($type === 'companies')<td>{{ $item->location }}</td><td>{{ $item->contact_person ?: 'Not specified' }}@if($item->phone)<span class="sub">{{ $item->phone }}</span>@endif</td>@endif<td>{{ $item->employees_count }}</td><td><span class="badge {{ $item->is_active ? 'active' : 'inactive' }}">{{ $item->is_active ? 'Active' : 'Inactive' }}</span></td>@if($canManage)<td><div class="actions"><a class="btn secondary small" href="{{ route($type.'.edit', $item->id) }}">Edit</a>@if(auth()->user()->isSuperAdmin())<form method="POST" action="{{ route($type.'.destroy', $item->id) }}" data-confirm="Delete this record?">@csrf @method('DELETE')<button class="btn danger small">Delete</button></form>@endif</div></td>@endif</tr>
@empty<tr><td colspan="{{ $type === 'companies' ? 6 : 4 }}"><div class="empty">No records found.</div></td></tr>@endforelse
</tbody></table></div>@include('partials.pagination', ['items' => $items])</div>
@if(!$canManage)<p class="help">Managers can view assigned companies. Admin maintains company master data; only Super Admin can delete.</p>@endif
@endsection
