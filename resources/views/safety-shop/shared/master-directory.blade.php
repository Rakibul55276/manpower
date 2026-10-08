@extends('layouts.app')
@section('title',ucfirst($type).' directory')
@section('eyebrow','Safety shop · Masters')
@section('description','Maintain approved values used consistently across Safety Shop transactions and product records.')
@section('actions')
@if(auth()->user()->canApprove())
<a class="btn" href="{{ route('safety-shop.'.$directory.'.create') }}">+ Add {{ $type }}</a>
@endif
@endsection
@section('content')
@include('safety-shop.shared.nav')
<div class="card"><div class="card-head"><h2>{{ ucfirst($type) }} records</h2><span class="badge">{{ $masters->total() }}</span></div><div class="table-wrap"><table><thead><tr><th>Name</th>@if($type==='category')<th>SKU prefix</th>@endif<th>Contact / details</th><th>Status</th><th></th></tr></thead><tbody>
@forelse($masters as $master)<tr><td><strong>{{ $master->name }}</strong>@if($type==='supplier' && $master->contact_person)<span class="sub">Contact: {{ $master->contact_person }}</span>@endif</td>@if($type==='category')<td><span class="badge">{{ $master->sku_prefix }}</span></td>@endif<td>{{ $master->phone }} {{ $master->email }}@if($type==='supplier' && ($master->vat_number || $master->commercial_registration))<span class="sub">{{ $master->vat_number?'VAT '.$master->vat_number:'' }} {{ $master->commercial_registration?'· CR '.$master->commercial_registration:'' }}</span>@endif<span class="sub">{{ $master->address }}</span>@if($type==='supplier' && $master->website)<span class="sub">{{ $master->website }}</span>@endif</td><td><span class="badge {{ $master->is_active?'approved':'cancelled' }}">{{ $master->is_active?'Active':'Inactive' }}</span></td><td>@if(auth()->user()->canApprove())<a href="{{ route('safety-shop.'.$directory.'.edit',$master) }}">Edit</a>@endif</td></tr>@empty<tr><td colspan="{{ $type==='category'?5:4 }}"><div class="empty">No {{ $type }} records yet.</div></td></tr>@endforelse
</tbody></table></div>@include('partials.pagination',['items'=>$masters])</div>
@endsection
