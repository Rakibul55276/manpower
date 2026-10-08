@extends('layouts.app')
@section('title','Safety Shop home')
@section('eyebrow','Harbour Edge · Safety Shop')
@section('description','Start a sale, check stock, serve customers, and review today’s shop activity from one clear workspace.')
@section('actions')
@if(auth()->user()->canApprove())<a class="btn" href="{{ route('safety-shop.sales.create') }}">Open barcode checkout</a>@endif
@endsection
@section('content')
@include('safety-shop.shared.nav')
<div class="card"><div class="card-head"><h2>What would you like to do?</h2><span class="badge">{{ now()->format('d M Y') }}</span></div><div class="card-body"><div class="quick-grid">
@if(auth()->user()->canApprove())<a class="quick-action" href="{{ route('safety-shop.sales.create') }}"><strong>Start a sale</strong><span>Scan products, select a customer and take split payment.</span></a><a class="quick-action" href="{{ route('safety-shop.stock.create') }}"><strong>Receive or move stock</strong><span>Record receipts, transfers and adjustments.</span></a>@endif
<a class="quick-action" href="{{ route('safety-shop.returns.index') }}"><strong>Process a return</strong><span>Find the original receipt and restore returned stock.</span></a>
<a class="quick-action" href="{{ route('safety-shop.customers.index') }}"><strong>Customer directory</strong><span>Retail customers and company purchasers in one place.</span></a>
<a class="quick-action" href="{{ route('safety-shop.products.index') }}"><strong>Products and stock</strong><span>Search stock, prices, barcodes and reorder levels.</span></a>
<a class="quick-action" href="{{ route('safety-shop.reports.index') }}"><strong>Reports and analytics</strong><span>Revenue, VAT, returns, cost and profitability.</span></a>
</div></div></div>
<div class="stats">
<div class="stat"><div class="label">Sales today</div><div class="value">{{ number_format($metrics['sales_today']) }}</div><small>SAR {{ \App\Services\Pay::money($metrics['revenue_today']) }} received</small></div>
<div class="stat"><div class="label">This month</div><div class="value">{{ \App\Services\Pay::money($metrics['revenue_month']) }}</div><small>Total incl. VAT · SAR</small></div>
<a class="stat stat-link" href="{{ route('safety-shop.customers.index') }}"><div class="label">Active customers</div><div class="value">{{ number_format($metrics['customers']) }}</div><small>{{ number_format($metrics['company_customers']) }} company purchasers</small></a>
<a class="stat stat-link" href="{{ route('safety-shop.products.index',['low'=>1]) }}"><div class="label">Low stock</div><div class="value">{{ number_format($metrics['low']) }}</div><small>{{ number_format($metrics['units']) }} units in {{ number_format($metrics['locations']) }} locations</small></a>
</div>
<div class="grid-2"><div class="card"><div class="card-head"><h2>Recent sales</h2><a href="{{ route('safety-shop.sales.index') }}">View all →</a></div><div class="table-wrap"><table><thead><tr><th>Receipt</th><th>Customer</th><th>Total</th></tr></thead><tbody>@forelse($recentSales as $sale)<tr><td><a href="{{ route('safety-shop.sales.show',$sale) }}">SALE-{{ $sale->id }}</a><span class="sub">{{ $sale->created_at->format('d M, h:i A') }}</span></td><td>{{ $sale->customer }}</td><td>SAR {{ \App\Services\Pay::money($sale->total_cents) }}</td></tr>@empty<tr><td colspan="3"><div class="empty">No sales yet. Open checkout to record the first sale.</div></td></tr>@endforelse</tbody></table></div></div>
<div class="card"><div class="card-head"><h2>Replenishment attention</h2><a href="{{ route('safety-shop.products.index',['low'=>1]) }}">Review all →</a></div><div class="table-wrap"><table><thead><tr><th>Product</th><th>On hand</th><th>Reorder at</th></tr></thead><tbody>@forelse($lowProducts as $product)<tr><td><a href="{{ route('safety-shop.products.show',$product) }}">{{ $product->name }}</a><span class="sub">{{ $product->sku }}</span></td><td>{{ number_format($product->stock_total) }}</td><td>{{ number_format($product->reorder_level) }}</td></tr>@empty<tr><td colspan="3"><div class="empty">All active products are above their reorder levels.</div></td></tr>@endforelse</tbody></table></div></div></div>
<style>.quick-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(210px,1fr));gap:12px}.quick-action{display:flex;flex-direction:column;gap:6px;padding:18px;border:1px solid #d7e2df;border-radius:12px;background:#f8fbfa;color:inherit;text-decoration:none}.quick-action:hover{border-color:#25877d;background:#eef8f5}.quick-action span{color:#617772;font-size:.9rem;line-height:1.4}</style>
@endsection
