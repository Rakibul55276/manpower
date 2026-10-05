@extends('layouts.app')
@section('title',ucfirst($type).' directory')
@section('eyebrow','Safety shop · Masters')
@section('description','Maintain inventory categories, suppliers, and stock locations.')
@section('actions')
@if(auth()->user()->canApprove())
<a class="btn" href="{{ route('safety-shop.'.$directory.'.create') }}">+ Add {{ $type }}</a>
@endif
@endsection
@section('content')
@include('safety-shop.shared.nav')
<div class="card"><div class="card-head"><h2>{{ ucfirst($type) }} records</h2><span class="badge">{{ $masters->total() }}</span></div><div class="table-wrap"><table><thead><tr><th>Name</th><th>Contact / details</th><th>Status</th><th></th></tr></thead><tbody>
@forelse($masters as $master)<tr><td><strong>{{ $master->name }}</strong></td><td>{{ $master->phone }} {{ $master->email }}<span class="sub">{{ $master->address }}</span></td><td><span class="badge {{ $master->is_active?'approved':'cancelled' }}">{{ $master->is_active?'Active':'Inactive' }}</span></td><td>@if(auth()->user()->canApprove())<a href="{{ route('safety-shop.'.$directory.'.edit',$master) }}">Edit</a>@endif</td></tr>@empty<tr><td colspan="4"><div class="empty">No {{ $type }} records yet.</div></td></tr>@endforelse
</tbody></table></div>@include('partials.pagination',['items'=>$masters])</div>
@endsection
