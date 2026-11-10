@extends('layouts.app')
@section('title','Safety shop inventory')
@section('eyebrow','Safety shop')
@section('description','Track PPE and safety equipment, stock availability, and replenishment.')
@section('head')<link rel="stylesheet" href="{{ asset('css/safety-shop/products/index.css') }}?v={{ is_file(public_path('css/safety-shop/products/index.css')) ? filemtime(public_path('css/safety-shop/products/index.css')) : '1' }}">@endsection
@section('actions')
@if(auth()->user()->canApprove())<a class="btn" href="{{ route('safety-shop.products.create') }}">+ Add product</a>@endif
@endsection
@section('content')
@include('safety-shop.shared.nav')
@php($showFinancial=auth()->user()->canApprove())
<div class="stats">
<div class="stat"><div class="label">Active products</div><div class="value">{{ $metrics['products'] }}</div></div>
<div class="stat"><div class="label">Units on hand</div><div class="value">{{ number_format($metrics['units']) }}</div></div>
@if($showFinancial)<div class="stat"><div class="label">Stock value at current cost · SAR</div><div class="value">{{ \App\Services\Pay::money($metrics['value']) }}</div></div>@endif
<a class="stat stat-link" href="{{ route('safety-shop.index',['low'=>1]) }}"><div class="label">Products needing replenishment</div><div class="value">{{ $metrics['low'] }}</div></a>
</div>
<div class="card">
<form class="filters" method="GET">
<div class="field search"><label for="search">Search</label><input id="search" name="search" value="{{ request('search') }}" placeholder="Barcode, SKU, product or brand"></div>
<div class="field"><label for="category">Category</label><select id="category" name="category"><option value="">All categories</option>@foreach($categories as $category)<option value="{{ $category->id }}" {{ request('category')==$category->id?'selected':'' }}>{{ $category->name }}</option>@endforeach</select></div>
<div class="field"><label for="status">Status</label><select id="status" name="status"><option value="">All statuses</option><option value="active" {{ request('status')==='active'?'selected':'' }}>Active</option><option value="inactive" {{ request('status')==='inactive'?'selected':'' }}>Inactive</option></select></div>
<div class="field"><label for="low">Stock level</label><select id="low" name="low"><option value="">All levels</option><option value="1" {{ request('low')?'selected':'' }}>Low stock</option></select></div>
@if($showFinancial)<div class="field"><label for="period">Performance period</label><select id="period" name="period"><option value="month" {{ request('period','month')==='month'?'selected':'' }}>Calendar month</option><option value="custom" {{ request('period')==='custom'?'selected':'' }}>Custom duration</option></select></div>
<div class="field" id="month-filter"><label for="month">Month</label><input id="month" name="month" type="month" value="{{ request('month',now()->format('Y-m')) }}"></div>
<div class="field" id="from-filter"><label for="from">From</label><input id="from" name="from" type="date" value="{{ request('from') }}"></div>
<div class="field" id="to-filter"><label for="to">To</label><input id="to" name="to" type="date" value="{{ request('to') }}"></div>@endif
<div class="field"><label for="per_page">Rows per page</label><select id="per_page" name="per_page">@foreach(\App\Support\DirectoryPagination::options() as $size)<option value="{{ $size }}" {{ $products->perPage()===$size?'selected':'' }}>{{ $size }}</option>@endforeach</select></div>
<button class="btn secondary">Filter</button><a class="btn link" href="{{ route('safety-shop.products.index') }}">Clear</a>
</form>
<div class="card-head"><h2>Product directory</h2>@if($showFinancial)<span class="badge">Performance: {{ $periodLabel }}</span><span class="product-performance-note">Sales performance is net of customer returns.</span>@endif<div class="product-view-toggle" role="group" aria-label="Product display"><button id="products-list-view" class="product-view-button active" type="button" aria-pressed="true">☷ List</button><button id="products-grid-view" class="product-view-button" type="button" aria-pressed="false">▦ Grid</button></div>@if($showFinancial)<a class="btn secondary" href="{{ route('safety-shop.export',request()->query()) }}">Export stock CSV</a>@endif</div>
<div id="products-list" class="table-wrap"><table><thead><tr><th>Product / SKU</th><th>Barcode</th><th>Category</th><th>Size / variant</th><th>Stock</th><th>Selling price (SAR)</th>@if($showFinancial)<th>Sales performance</th>@endif<th>Status</th></tr></thead><tbody>
@forelse($products as $product)
@php $revenue=(int)$product->period_revenue_cents; $profit=$revenue-(int)$product->period_cost_cents; $margin=$revenue>0?round($profit/$revenue*100,2):0; @endphp
<tr><td class="product-name-cell"><a href="{{ route('safety-shop.products.show',$product) }}"><strong>{{ $product->name }}</strong></a><span class="sub">SKU: {{ $product->sku }}</span></td><td><span style="font-family:ui-monospace,SFMono-Regular,Consolas,monospace;white-space:nowrap">{{ $product->barcode ?: '—' }}</span></td><td>{{ optional($product->category)->name ?: '—' }}</td><td>{{ $product->size ?: '—' }}@if($product->variant)<span class="sub">{{ $product->variant }}</span>@endif</td><td><strong>{{ number_format($product->stock_total) }} {{ $product->unit }}</strong>@if($product->is_active && $product->stock_total <= $product->reorder_level)<span class="badge pending">Low stock</span>@endif</td><td>{{ \App\Services\Pay::money($product->price_cents) }}<span class="sub">{{ $product->price_includes_vat?'VAT included':'VAT excluded' }}</span></td>@if($showFinancial)<td class="product-performance"><strong>{{ number_format($product->period_sold_quantity) }} sold · Revenue excl. VAT SAR {{ \App\Services\Pay::money($revenue) }}</strong><span class="sub">Gross profit SAR {{ \App\Services\Pay::money($profit) }} · Margin {{ number_format($margin,2) }}%</span></td>@endif<td><span class="badge {{ $product->is_active?'approved':'cancelled' }}">{{ $product->is_active?'Active':'Inactive' }}</span></td></tr>
@empty<tr><td colspan="{{ $showFinancial?8:7 }}"><div class="empty"><strong>No products found.</strong><p>Add categories and locations, create a product, then receive its opening stock.</p></div></td></tr>@endforelse
</tbody></table></div>
<div id="products-grid" class="product-directory-grid" hidden>
@forelse($products as $product)
@php $gridRevenue=(int)$product->period_revenue_cents; $gridProfit=$gridRevenue-(int)$product->period_cost_cents; $gridSold=(int)$product->period_sold_quantity; @endphp
<a class="product-directory-card" href="{{ route('safety-shop.products.show',$product) }}">
@if($product->image_path)<img class="product-directory-image" src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($product->image_path) }}" alt="{{ $product->name }}" loading="lazy" data-image-preview>@else<img class="product-directory-image" src="{{ request()->getBaseUrl() }}/images/product-placeholder.svg" alt="" loading="lazy">@endif
<div class="product-directory-content"><div class="product-directory-top"><span class="badge {{ $product->is_active?'approved':'cancelled' }}">{{ $product->is_active?'Active':'Inactive' }}</span>@if($product->is_active && $product->stock_total <= $product->reorder_level)<span class="badge pending">Low stock</span>@endif</div><h3>{{ $product->name }}</h3><span class="sub">{{ $product->sku }} · {{ $product->barcode ?: 'No barcode' }}</span><dl><div><dt>Category</dt><dd>{{ optional($product->category)->name ?: '—' }}</dd></div><div><dt>Brand / size</dt><dd>{{ collect([$product->brand,$product->size,$product->variant])->filter()->implode(' · ') ?: '—' }}</dd></div><div><dt>Stock</dt><dd><strong>{{ number_format($product->stock_total) }} {{ $product->unit }}</strong></dd></div><div><dt>Price</dt><dd>SAR {{ \App\Services\Pay::money($product->price_cents) }}</dd></div>@if($showFinancial)<div><dt>{{ $periodLabel }} sales</dt><dd>{{ number_format($gridSold) }} units</dd></div><div><dt>Revenue excl. VAT / gross profit</dt><dd>SAR {{ \App\Services\Pay::money($gridRevenue) }} / {{ \App\Services\Pay::money($gridProfit) }}</dd></div>@endif</dl></div>
</a>
@empty<div class="empty"><strong>No products found.</strong><p>Add categories and locations, create a product, then receive its opening stock.</p></div>@endforelse
</div>
@include('partials.pagination',['items'=>$products])
</div>
@if($showFinancial)<script>(()=>{const period=document.getElementById('period'),month=document.getElementById('month'),from=document.getElementById('from'),to=document.getElementById('to'),monthWrap=document.getElementById('month-filter'),fromWrap=document.getElementById('from-filter'),toWrap=document.getElementById('to-filter');const sync=()=>{const custom=period.value==='custom';monthWrap.hidden=custom;month.disabled=custom;fromWrap.hidden=!custom;toWrap.hidden=!custom;from.disabled=!custom;to.disabled=!custom;from.required=custom;to.required=custom;};period.addEventListener('change',sync);sync();})();</script>@endif
<script>(()=>{const list=document.getElementById('products-list'),grid=document.getElementById('products-grid'),listButton=document.getElementById('products-list-view'),gridButton=document.getElementById('products-grid-view');if(!list||!grid)return;const setView=view=>{const gridView=view==='grid';list.hidden=gridView;grid.hidden=!gridView;listButton.classList.toggle('active',!gridView);gridButton.classList.toggle('active',gridView);listButton.setAttribute('aria-pressed',String(!gridView));gridButton.setAttribute('aria-pressed',String(gridView));try{localStorage.setItem('safety-shop-product-view',view)}catch(error){}};listButton.addEventListener('click',()=>setView('list'));gridButton.addEventListener('click',()=>setView('grid'));let saved='list';try{saved=localStorage.getItem('safety-shop-product-view')||'list'}catch(error){}setView(saved);})();</script>
@endsection
