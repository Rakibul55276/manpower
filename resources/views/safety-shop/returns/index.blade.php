@extends('layouts.app')
@section('title','Customer returns')
@section('eyebrow','Safety shop · Returns directory')
@section('description','Review receipt-linked returns, refund methods, restored stock, and the responsible user.')
@section('actions')
@if(auth()->user()->canApprove())
<a class="btn" href="{{ route('safety-shop.returns.create') }}">Process customer return</a>
@endif
@endsection
@section('content')
@include('safety-shop.shared.nav')
<div class="card"><div class="table-wrap"><table><thead><tr><th>Return</th><th>Original receipt</th><th>Customer</th><th>Location</th><th>Refund SAR</th><th>Method</th><th>Processed by</th></tr></thead><tbody>
@forelse($returns as $return)<tr><td><a href="{{ route('safety-shop.returns.show',$return) }}"><strong>RETURN-{{ $return->id }}</strong></a><span class="sub">{{ $return->created_at->format('d M Y H:i') }}</span></td><td><a href="{{ route('safety-shop.sales.show',$return->sale) }}">SALE-{{ $return->sale_id }}</a></td><td>{{ $return->sale->customer }}@if($return->sale->customer_phone)<span class="sub">{{ $return->sale->customer_phone }}</span>@endif</td><td>{{ $return->location->name }}</td><td>{{ \App\Services\Pay::money($return->refund_cents) }}</td><td>{{ ucwords(str_replace('_',' ',$return->refund_method)) }}</td><td>{{ $return->creator->name }}</td></tr>@empty<tr><td colspan="7"><div class="empty">No customer returns have been posted.</div></td></tr>@endforelse
</tbody></table></div>@include('partials.pagination',['items'=>$returns])</div>
@endsection
