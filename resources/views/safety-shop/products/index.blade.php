@extends('layouts.app')
@section('title','Safety shop inventory')
@section('eyebrow','Safety shop')
@section('description','Track PPE and safety equipment, stock availability, and replenishment.')
@section('actions')
@if(auth()->user()->canApprove())<a class="btn" href="{{ route('safety-shop.products.create') }}">+ Add product</a>@endif
@endsection
@section('content')
@include('safety-shop.shared.nav')
<div class="stats">
<div class="stat"><div class="label">Active products</div><div class="value">{{ $metrics['products'] }}</div></div>
<div class="stat"><div class="label">Units on hand</div><div class="value">{{ number_format($metrics['units']) }}</div></div>
<div class="stat"><div class="label">Stock value at current cost · SAR</div><div class="value">{{ \App\Services\Pay::money($metrics['value']) }}</div></div>
<a class="stat stat-link" href="{{ route('safety-shop.index',['low'=>1]) }}"><div class="label">Products needing replenishment</div><div class="value">{{ $metrics['low'] }}</div></a>
</div>
<div class="card"><form class="filters" method="GET">
<div class="field search"><label for="search">Search</label><input id="search" name="search" value="{{ request('search') }}" placeholder="SKU, product or brand"></div>
<div class="field"><label for="category">Category</label><select id="category" name="category"><option value="">All categories</option>@foreach($categories as $category)<option value="{{ $category->id }}" {{ request('category')==$category->id?'selected':'' }}>{{ $category->name }}</option>@endforeach</select></div>
<div class="field"><label for="status">Status</label><select id="status" name="status"><option value="">All statuses</option><option value="active" {{ request('status')==='active'?'selected':'' }}>Active</option><option value="inactive" {{ request('status')==='inactive'?'selected':'' }}>Inactive</option></select></div>
<div class="field"><label for="low">Stock level</label><select id="low" name="low"><option value="">All levels</option><option value="1" {{ request('low')?'selected':'' }}>Low stock</option></select></div>
<button class="btn secondary">Filter</button><a class="btn link" href="{{ route('safety-shop.index') }}">Clear</a>
</form><div class="card-head"><h2>Product directory</h2><a class="btn secondary" href="{{ route('safety-shop.export',request()->query()) }}">Export stock CSV</a></div>
<div class="table-wrap"><table><thead><tr><th>Product / SKU</th><th>Category</th><th>Brand / size</th><th>Stock</th><th>Reorder level</th><th>Cost / price SAR</th><th>Status</th></tr></thead><tbody>
@forelse($products as $product)<tr><td><a href="{{ route('safety-shop.products.show',$product) }}"><strong>{{ $product->name }}</strong></a><span class="sub">{{ $product->sku }}</span></td><td>{{ optional($product->category)->name ?: '—' }}</td><td>{{ $product->brand ?: '—' }}<span class="sub">{{ $product->size }}</span></td><td><strong>{{ number_format($product->stock_total) }} {{ $product->unit }}</strong>@if($product->is_active && $product->stock_total <= $product->reorder_level)<span class="badge pending">Low stock</span>@endif</td><td>{{ $product->reorder_level }}</td><td>{{ \App\Services\Pay::money($product->cost_cents) }} / {{ \App\Services\Pay::money($product->price_cents) }}</td><td><span class="badge {{ $product->is_active?'approved':'cancelled' }}">{{ $product->is_active?'Active':'Inactive' }}</span></td></tr>
@empty<tr><td colspan="7"><div class="empty"><strong>No products found.</strong><p>Add categories and locations, create a product, then receive its opening stock.</p></div></td></tr>@endforelse
</tbody></table></div>@include('partials.pagination',['items'=>$products])</div>
@endsection
