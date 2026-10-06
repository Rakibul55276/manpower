@extends('layouts.app')
@section('title','Customer directory')
@section('eyebrow','Safety shop · Master data')
@section('description','Maintain reusable customer details captured from checkout and manual entry.')
@section('actions')
@if(auth()->user()->canApprove())<a class="btn" href="{{ route('safety-shop.customers.create') }}">+ Add customer</a>@endif
@endsection
@section('content')
@include('safety-shop.shared.nav')
<div class="card"><form class="filters" method="GET"><div class="field search"><label for="customer-search">Search</label><input id="customer-search" name="search" value="{{ request('search') }}" placeholder="Name, mobile or email"></div><div class="field"><label for="customer-status">Status</label><select id="customer-status" name="status"><option value="">All statuses</option><option value="active" {{ request('status')==='active'?'selected':'' }}>Active</option><option value="inactive" {{ request('status')==='inactive'?'selected':'' }}>Inactive</option></select></div><button class="btn secondary">Filter</button><a class="btn link" href="{{ route('safety-shop.customers.index') }}">Clear filters</a></form>
<div class="card-head"><h2>Customer master records</h2><span class="badge">{{ $customers->total() }}</span></div><div class="table-wrap"><table><thead><tr><th>Customer</th><th>Contact</th><th>Address</th><th>Purchases</th><th>Lifetime sales SAR</th><th>Last purchase</th><th>Status</th><th></th></tr></thead><tbody>
@forelse($customers as $customer)<tr><td><strong>{{ $customer->name }}</strong></td><td>{{ $customer->phone ?: '—' }}<span class="sub">{{ $customer->email }}</span></td><td>{{ $customer->address ?: '—' }}</td><td>{{ number_format($customer->purchase_count) }}</td><td>{{ \App\Services\Pay::money($customer->lifetime_value_cents) }}</td><td>{{ optional($customer->last_purchase_at)->format('d M Y H:i') ?: '—' }}</td><td><span class="badge {{ $customer->is_active?'approved':'cancelled' }}">{{ $customer->is_active?'Active':'Inactive' }}</span></td><td>@if(auth()->user()->canApprove())<a href="{{ route('safety-shop.customers.edit',$customer) }}">Edit</a>@endif</td></tr>@empty<tr><td colspan="8"><div class="empty">No customers found. Customers entered during checkout will appear here automatically.</div></td></tr>@endforelse
</tbody></table></div>@include('partials.pagination',['items'=>$customers])</div>
@endsection
