@extends('layouts.app')
@section('title','Safety shop invoices')
@section('eyebrow','Safety shop · VAT invoices')
@section('description','Review VAT invoices and payments recorded by the safety shop.')
@section('actions')
@if(auth()->user()->canOperateSafetyShop())
<a class="btn secondary" href="{{ route('safety-shop.returns.create') }}">Process return</a>
<a class="btn" href="{{ route('safety-shop.sales.create') }}">New sale</a>
@endif
@endsection
@section('content')
@include('safety-shop.shared.nav')
<div class="card"><div class="card-body"><form method="GET" class="form-grid"><div class="field full"><label for="invoice-search">Search invoices</label><div style="display:flex;gap:10px"><input id="invoice-search" name="q" value="{{ $search }}" placeholder="Invoice number, customer PO number, or customer"><button class="btn" type="submit">Search</button>@if($search)<a class="btn secondary" href="{{ route('safety-shop.sales.index') }}">Clear</a>@endif</div></div></form></div><div class="table-wrap"><table><thead><tr><th>Invoice</th><th>Customer / PO</th><th>Location</th><th>Total SAR</th><th>Payment</th><th>Sold by</th></tr></thead><tbody>
@forelse($sales as $sale)<tr><td><a href="{{ route('safety-shop.sales.show',$sale) }}"><strong>{{ $sale->invoice_number }}</strong></a><span class="sub">{{ $sale->created_at->format('d M Y H:i') }}</span>@if($sale->status==='draft')<span class="badge pending">DRAFT</span><span class="sub"><a href="{{ route('safety-shop.sales.draft',$sale) }}">Continue editing</a></span>@elseif($sale->status==='void')<span class="badge cancelled">VOID</span>@elseif($sale->replaces_sale_id)<span class="badge approved">Replacement</span>@endif</td><td>{{ $sale->customer }}@if($sale->customer_po_number)<span class="sub">PO: {{ $sale->customer_po_number }}</span>@endif @if($sale->customer_phone)<span class="sub">{{ $sale->customer_phone }}</span>@endif</td><td>{{ $sale->location->name }}</td><td>{{ \App\Services\Pay::money($sale->total_cents) }}@if($sale->discount_cents)<span class="sub">Discount {{ \App\Services\Pay::money($sale->discount_cents) }}</span>@endif</td><td>{{ $sale->payment_status==='partially_paid'?'Partially paid':ucfirst($sale->payment_status) }}<span class="sub">{{ $sale->payment_method==='split'?'Split payment':ucfirst($sale->payment_method) }} · SAR {{ \App\Services\Pay::money($sale->paid_cents) }}</span></td><td>{{ $sale->creator->name }}</td></tr>@empty<tr><td colspan="6"><div class="empty">No invoices or drafts found.</div></td></tr>@endforelse
</tbody></table></div>@include('partials.pagination',['items'=>$sales])</div>
@endsection
