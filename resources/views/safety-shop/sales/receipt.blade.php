@extends('layouts.app')
@section('title','Sale receipt SALE-'.$sale->id)
@section('eyebrow','Safety shop · Sale receipt')
@section('description','Stock sale receipt · '.$sale->created_at->format('d M Y H:i'))
@section('content')
<div class="shop-no-print">@include('safety-shop.shared.nav')</div>
<div class="card safety-shop-receipt"><div class="card-head"><h2>SALE-{{ $sale->id }}</h2><button class="btn secondary shop-no-print" id="shop-print-receipt" type="button">Print receipt</button></div><div class="card-body"><p><strong>Customer:</strong> {{ $sale->customer }}</p><p><strong>Shop location:</strong> {{ $sale->location->name }}</p><p><strong>Sold by:</strong> {{ $sale->creator->name }}</p></div>
<div class="table-wrap"><table><thead><tr><th>Product / barcode</th><th>Quantity</th><th>Price SAR</th><th>Total SAR</th></tr></thead><tbody>@foreach($sale->lines as $line)<tr><td><strong>{{ $line->name }}</strong><span class="sub">{{ $line->sku }} · {{ $line->barcode ?: 'No barcode' }}</span></td><td>{{ $line->quantity }} {{ $line->unit }}</td><td>{{ \App\Services\Pay::money($line->price_cents) }}</td><td>{{ \App\Services\Pay::money($line->total_cents) }}</td></tr>@endforeach</tbody></table></div>
<div class="card-body"><p><strong>Total: SAR {{ \App\Services\Pay::money($sale->total_cents) }}</strong></p><p>Payment: {{ ucfirst($sale->payment_method) }} · SAR {{ \App\Services\Pay::money($sale->paid_cents) }} received</p><p>Change: SAR {{ \App\Services\Pay::money($sale->paid_cents-$sale->total_cents) }}</p><p class="help">This is a safety shop stock sale receipt. Tax invoicing is handled separately. For a return, post a stock return referencing SALE-{{ $sale->id }}; refunds are recorded separately from inventory.</p></div></div>
<link rel="stylesheet" href="{{ asset('css/safety-shop/sales/sales.css') }}">
<script src="{{ asset('js/safety-shop/sales/checkout.js') }}" defer></script>
@endsection
