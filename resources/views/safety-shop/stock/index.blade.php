@extends('layouts.app')
@section('title','Stock movement ledger')
@section('eyebrow','Safety shop · Reports')
@section('description','Review the permanent stock history and export filtered movements.')
@section('content')
@include('safety-shop.shared.nav')
<div class="card"><form method="GET" class="filters">
<div class="field"><label for="type">Type</label><select id="type" name="type"><option value="">All types</option>@foreach(['receipt','issue','return','adjustment','transfer'] as $type)<option value="{{ $type }}" {{ request('type')===$type?'selected':'' }}>{{ ucfirst($type) }}</option>@endforeach</select></div>
<div class="field"><label for="product_id">Product</label><select id="product_id" name="product_id"><option value="">All products</option>@foreach($products as $product)<option value="{{ $product->id }}" {{ request('product_id')==$product->id?'selected':'' }}>{{ $product->sku }} · {{ $product->name }}</option>@endforeach</select></div>
<div class="field"><label for="location_id">Location</label><select id="location_id" name="location_id"><option value="">All locations</option>@foreach($locations as $location)<option value="{{ $location->id }}" {{ request('location_id')==$location->id?'selected':'' }}>{{ $location->name }}</option>@endforeach</select></div>
<div class="field"><label for="from">From</label><input id="from" type="date" name="from" value="{{ request('from') }}"></div><div class="field"><label for="to">To</label><input id="to" type="date" name="to" value="{{ request('to') }}"></div>
<button class="btn secondary">Filter</button><a class="btn link" href="{{ route('safety-shop.stock.index') }}">Clear</a></form>
<div class="card-head"><h2>Movements</h2>@if(auth()->user()->isSuperAdmin())<a class="btn secondary" href="{{ route('safety-shop.export',array_merge(request()->query(),['report'=>'movements'])) }}">Export ledger CSV</a>@endif</div>
@include('safety-shop.stock.ledger')
</div>
@endsection
