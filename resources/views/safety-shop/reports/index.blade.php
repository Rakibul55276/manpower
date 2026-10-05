@extends('layouts.app')
@section('title','Inventory reports')
@section('eyebrow','Safety shop · Reports')
@section('description','Export product balances or the stock movement ledger for reconciliation.')
@section('content')
@include('safety-shop.shared.nav')
<div class="grid-2">
<div class="card"><div class="card-head"><h2>Stock report</h2></div><div class="card-body"><p>Current quantities, reorder levels, costs, and selling prices.</p><form action="{{ route('safety-shop.export') }}" method="GET"><div class="field"><label for="low">Stock level</label><select id="low" name="low"><option value="">All products</option><option value="1">Low stock only</option></select></div><div class="form-footer"><button class="btn">Download stock CSV</button></div></form></div></div>
<div class="card"><div class="card-head"><h2>Movement report</h2></div><div class="card-body"><p>Permanent receipt, issue, return, adjustment, and transfer history.</p><form action="{{ route('safety-shop.export') }}" method="GET"><input type="hidden" name="report" value="movements"><div class="form-grid"><div class="field"><label for="from">From date</label><input id="from" type="date" name="from"></div><div class="field"><label for="to">To date</label><input id="to" type="date" name="to"></div></div><div class="form-footer"><button class="btn">Download movement CSV</button></div></form></div></div>
</div>
@endsection
