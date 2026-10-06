@extends('layouts.app')
@section('title','Safety shop sales')
@section('eyebrow','Safety shop · Sales receipts')
@section('description','Review stock sales and payments recorded by the safety shop.')
@section('actions')
@if(auth()->user()->canApprove())
<a class="btn secondary" href="{{ route('safety-shop.returns.create') }}">Process return</a>
<a class="btn" href="{{ route('safety-shop.sales.create') }}">New sale</a>
@endif
@endsection
@section('content')
@include('safety-shop.shared.nav')
<div class="card"><div class="table-wrap"><table><thead><tr><th>Receipt</th><th>Customer</th><th>Location</th><th>Total SAR</th><th>Payment</th><th>Sold by</th></tr></thead><tbody>
@forelse($sales as $sale)<tr><td><a href="{{ route('safety-shop.sales.show',$sale) }}"><strong>SALE-{{ $sale->id }}</strong></a><span class="sub">{{ $sale->created_at->format('d M Y H:i') }}</span></td><td>{{ $sale->customer }}@if($sale->customer_phone)<span class="sub">{{ $sale->customer_phone }}</span>@endif</td><td>{{ $sale->location->name }}</td><td>{{ \App\Services\Pay::money($sale->total_cents) }}@if($sale->discount_cents)<span class="sub">Discount {{ \App\Services\Pay::money($sale->discount_cents) }}</span>@endif</td><td>{{ $sale->payment_method==='split'?'Split payment':ucfirst($sale->payment_method) }}</td><td>{{ $sale->creator->name }}</td></tr>@empty<tr><td colspan="6"><div class="empty">No sales yet. Add barcodes and prices to products, receive stock, then open barcode checkout.</div></td></tr>@endforelse
</tbody></table></div>@include('partials.pagination',['items'=>$sales])</div>
@endsection
