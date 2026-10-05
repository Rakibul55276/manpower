@extends('layouts.app')
@section('title',$product->name)
@section('eyebrow','Safety shop · Product details')
@section('description',$product->sku.' · '.$product->unit)
@section('actions')
@if(auth()->user()->canApprove())<a class="btn secondary" href="{{ route('safety-shop.products.edit',$product) }}">Edit product</a>@if($product->is_active)<a class="btn" href="{{ route('safety-shop.stock.create',['product_id'=>$product->id]) }}">+ Stock movement</a>@endif
@endif
@endsection
@section('content')
@include('safety-shop.shared.nav')
<div class="stats"><div class="stat"><div class="label">Total on hand</div><div class="value">{{ number_format($product->stocks->sum('quantity')) }}</div><div class="note">{{ $product->unit }}</div></div><div class="stat"><div class="label">Reorder level</div><div class="value">{{ $product->reorder_level }}</div></div><div class="stat"><div class="label">Unit cost / price SAR</div><div class="value">{{ \App\Services\Pay::money($product->cost_cents) }} / {{ \App\Services\Pay::money($product->price_cents) }}</div></div></div>
<div class="grid-2"><div class="card"><div class="card-head"><h2>Product information</h2><span class="badge {{ $product->is_active?'approved':'cancelled' }}">{{ $product->is_active?'Active':'Inactive' }}</span></div><div class="card-body"><p><strong>Barcode:</strong> {{ $product->barcode ?: '—' }}</p><p><strong>Category:</strong> {{ optional($product->category)->name ?: '—' }}</p><p><strong>Brand / size:</strong> {{ $product->brand ?: '—' }} / {{ $product->size ?: '—' }}</p><p><strong>Safety standard:</strong> {{ $product->safety_standard ?: '—' }}</p><p>{{ $product->notes }}</p></div></div>
<div class="card"><div class="card-head"><h2>Stock by location</h2></div><div class="table-wrap"><table><thead><tr><th>Location</th><th>Quantity</th></tr></thead><tbody>@forelse($product->stocks as $stock)<tr><td>{{ $stock->location->name }}{{ $stock->location->is_active?'':' (inactive)' }}</td><td>{{ $stock->quantity }} {{ $product->unit }}</td></tr>@empty<tr><td colspan="2"><div class="empty">No opening stock received.</div></td></tr>@endforelse</tbody></table></div></div></div>
<div class="card"><div class="card-head"><h2>Product movement history</h2><a href="{{ route('safety-shop.export',['report'=>'movements','product_id'=>$product->id]) }}">Export CSV</a></div>@include('safety-shop.stock.ledger')</div>
@endsection
