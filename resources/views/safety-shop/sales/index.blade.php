@extends('layouts.app')
@section('title','Safety shop sales')
@section('eyebrow','Safety shop · Sales receipts')
@section('description','Review stock sales and payments recorded by the safety shop.')
@section('content')
@include('safety-shop.shared.nav')
<div class="card"><div class="table-wrap"><table><thead><tr><th>Receipt</th><th>Customer</th><th>Location</th><th>Total SAR</th><th>Payment</th><th>Sold by</th></tr></thead><tbody>
@forelse($sales as $sale)<tr><td><a href="{{ route('safety-shop.sales.show',$sale) }}"><strong>SALE-{{ $sale->id }}</strong></a><span class="sub">{{ $sale->created_at->format('d M Y H:i') }}</span></td><td>{{ $sale->customer }}</td><td>{{ $sale->location->name }}</td><td>{{ \App\Services\Pay::money($sale->total_cents) }}</td><td>{{ ucfirst($sale->payment_method) }}</td><td>{{ $sale->creator->name }}</td></tr>@empty<tr><td colspan="6"><div class="empty">No sales yet. Add barcodes and prices to products, receive stock, then open barcode checkout.</div></td></tr>@endforelse
</tbody></table></div>@include('partials.pagination',['items'=>$sales])</div>
@endsection
