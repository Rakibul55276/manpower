@extends('layouts.app')
@section('title','Safety shop analytics & audit')
@section('eyebrow','Safety shop · Financial control')
@section('description','Monitor revenue, refunds, inventory cost, profit or loss, and export a traceable audit report.')
@section('content')
@include('safety-shop.shared.nav')
<div class="card"><form class="filters" method="GET">
<div class="field"><label for="analytics-from">From date</label><input id="analytics-from" type="date" name="from" value="{{ request('from',$from->format('Y-m-d')) }}"></div>
<div class="field"><label for="analytics-to">To date</label><input id="analytics-to" type="date" name="to" value="{{ request('to',$to->format('Y-m-d')) }}"></div>
<button class="btn secondary">Apply period</button><a class="btn link" href="{{ route('safety-shop.reports.index') }}">Clear filters</a>
<a class="btn secondary" href="{{ route('safety-shop.reports.financial.csv',request()->query()) }}">Download audit CSV</a><a class="btn" href="{{ route('safety-shop.reports.financial.pdf',request()->query()) }}">Generate audit PDF</a>
</form></div>
<div class="stats">
<div class="stat"><div class="label">Gross sales · SAR</div><div class="value">{{ \App\Services\Pay::money($summary['gross_sales']) }}</div><span class="sub">{{ number_format($summary['sales_count']) }} receipts</span></div>
<div class="stat"><div class="label">Refunds · SAR</div><div class="value">{{ \App\Services\Pay::money($summary['refunds']) }}</div><span class="sub">{{ number_format($summary['return_count']) }} returns</span></div>
<div class="stat"><div class="label">Net revenue excl. VAT · SAR</div><div class="value">{{ \App\Services\Pay::money($summary['net_revenue']) }}</div><span class="sub">VAT payable {{ \App\Services\Pay::money($summary['vat_collected']) }}</span></div>
<div class="stat"><div class="label">Net cost of goods · SAR</div><div class="value">{{ \App\Services\Pay::money($summary['net_cogs']) }}</div><span class="sub">After returned stock cost</span></div>
<div class="stat"><div class="label">Gross profit / loss · SAR</div><div class="value">{{ \App\Services\Pay::money($summary['gross_profit']) }}</div><span class="badge {{ $summary['gross_profit']>=0?'approved':'cancelled' }}">{{ $summary['gross_profit']>=0?'Profit':'Loss' }}</span></div>
<div class="stat"><div class="label">Gross margin</div><div class="value">{{ number_format($summary['margin'],2) }}%</div><span class="sub">Discounts {{ \App\Services\Pay::money($summary['discounts']) }}</span></div>
</div>
<div class="grid-2">
<div class="card"><div class="card-head"><h2>Revenue trend</h2><span class="badge approved">Net of returns · excl. VAT</span></div><div class="card-body">
@php($trendMax=max(1,(int)$trend->max(fn($row)=>max($row['revenue'],$row['returns']))))
@forelse($trend as $row)<div style="display:grid;grid-template-columns:90px 1fr 100px;gap:12px;align-items:center;margin:12px 0"><small>{{ \Carbon\Carbon::parse($row['day'])->format('d M') }}</small><div><div style="height:9px;background:#23877c;width:{{ max(2,round($row['revenue']/$trendMax*100)) }}%;margin-bottom:4px" title="Revenue"></div>@if($row['returns'])<div style="height:7px;background:#c65d4b;width:{{ max(2,round($row['returns']/$trendMax*100)) }}%" title="Returns"></div>@endif</div><strong style="text-align:right">{{ \App\Services\Pay::money($row['net']) }}</strong></div>@empty<div class="empty">No sales or returns in this period.</div>@endforelse
</div></div>
<div class="card"><div class="card-head"><h2>Calculation guide</h2></div><div class="card-body"><p><strong>Net revenue</strong> = sales after discounts, excluding VAT, less customer refunds excluding returned VAT.</p><p><strong>Net cost</strong> = historical cost of sold items less the historical cost restored by returns.</p><p><strong>Gross profit/loss</strong> = net revenue − net cost. Operating expenses are not included.</p></div></div>
</div>
<div class="card"><div class="card-head"><h2>Financial audit trail</h2><span class="badge">Latest {{ $events->count() }} in period</span></div><div class="table-wrap"><table><thead><tr><th>Date / transaction</th><th>Customer</th><th>Gross</th><th>Discount</th><th>VAT</th><th>Refund</th><th>Net revenue</th><th>Cost</th><th>Profit / loss</th><th>User</th></tr></thead><tbody>
@forelse($events as $event)<tr><td><strong>{{ $event->number }}</strong><span class="sub">{{ $event->date->format('d M Y H:i') }} · {{ $event->type }}@if($event->reference!=='—') · {{ $event->reference }}@endif</span></td><td>{{ $event->customer }}<span class="sub">{{ $event->location }}</span></td><td>{{ \App\Services\Pay::money($event->gross) }}</td><td>{{ \App\Services\Pay::money($event->discount) }}</td><td>{{ \App\Services\Pay::money($event->vat) }}</td><td>{{ \App\Services\Pay::money($event->refund) }}</td><td>{{ \App\Services\Pay::money($event->revenue) }}</td><td>{{ \App\Services\Pay::money($event->cost) }}</td><td><strong>{{ \App\Services\Pay::money($event->profit) }}</strong></td><td>{{ $event->user }}<span class="sub">{{ ucwords(str_replace('_',' ',$event->method)) }}</span></td></tr>@empty<tr><td colspan="10"><div class="empty">No financial transactions in this period.</div></td></tr>@endforelse
</tbody></table></div></div>
<h2 style="margin-top:28px">Inventory exports</h2>
<div class="grid-2">
<div class="card"><div class="card-head"><h2>Stock report</h2></div><div class="card-body"><p>Current quantities, reorder levels, costs, and selling prices.</p><form action="{{ route('safety-shop.export') }}" method="GET"><div class="field"><label for="low">Stock level</label><select id="low" name="low"><option value="">All products</option><option value="1">Low stock only</option></select></div><div class="form-footer"><button class="btn">Download stock CSV</button></div></form></div></div>
<div class="card"><div class="card-head"><h2>Movement report</h2></div><div class="card-body"><p>Permanent receipt, issue, return, adjustment, and transfer history.</p><form action="{{ route('safety-shop.export') }}" method="GET"><input type="hidden" name="report" value="movements"><div class="form-grid"><div class="field"><label for="from">From date</label><input id="from" type="date" name="from"></div><div class="field"><label for="to">To date</label><input id="to" type="date" name="to"></div></div><div class="form-footer"><button class="btn">Download movement CSV</button></div></form></div></div>
</div>
@endsection
