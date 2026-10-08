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
<div class="card"><div class="card-head"><h2>{{ ucfirst($type) }} directory</h2><span class="badge">{{ $items->total() }} records</span></div><div class="table-wrap"><table><thead><tr><th>Name</th>@if($type === 'companies')<th>Location</th><th>Contact</th>@if(config('saas.saas_voucher_enabled'))<th>Subscription</th>@endif @endif<th>Employees</th><th>Status</th>@if($canManage)<th>Actions</th>@endif</tr></thead><tbody>
@forelse($items as $item)<tr><td><strong>{{ $item->name }}</strong>@if($type === 'companies')<span class="sub">{{ $item->company_code }}</span>@if($item->registration_number)<span class="sub">CR: {{ $item->registration_number }}</span>@endif @endif</td>@if($type === 'companies')<td>{{ $item->location }}</td><td>{{ $item->contact_person ?: 'Not specified' }}@if($item->phone)<span class="sub">{{ $item->phone }}</span>@endif</td>@if(config('saas.saas_voucher_enabled'))<td><span class="badge {{ $item->hasApplicationAccess() ? 'active' : 'inactive' }}">{{ $item->subscriptionState() }}</span>@if($item->subscription_expires_at)<span class="sub">Until {{ $item->subscription_expires_at->format('d M Y') }}</span>@endif</td>@endif @endif<td>{{ $item->employees_count }}</td><td><span class="badge {{ $item->is_active ? 'active' : 'inactive' }}">{{ $item->is_active ? 'Active' : 'Inactive' }}</span></td>@if($canManage)<td><div class="actions">@if($type === 'companies')<a class="btn secondary small" href="{{ route('companies.branches.index',$item) }}">Branches</a>@endif<a class="btn secondary small" href="{{ route($type.'.edit', $item->id) }}">Edit</a>@if(auth()->user()->isSuperAdmin())<form method="POST" action="{{ route($type.'.destroy', $item->id) }}" data-confirm="Delete this record?">@csrf @method('DELETE')<button class="btn danger small">Delete</button></form>@endif</div></td>@endif</tr>
@empty<tr><td colspan="{{ $type === 'companies' ? 6 : 4 }}"><div class="empty">No records found.</div></td></tr>@endforelse
</tbody></table></div>@include('partials.pagination', ['items' => $items])</div>
@if(!$canManage)<p class="help">Managers can view assigned companies. Admin maintains company master data; only Super Admin can delete.</p>@endif
@endsection
