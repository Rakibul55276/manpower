@extends('layouts.app')
@section('title','Return RETURN-'.$return->id)
@section('eyebrow','Safety shop · Customer return confirmation')
@section('description','Posted return linked to the original receipt and inventory ledger.')
@section('actions')<a class="btn secondary" href="{{ route('safety-shop.sales.show',$return->sale) }}">Original receipt</a><a class="btn" href="{{ route('safety-shop.returns.create') }}">New return</a>@endsection
@section('content')
@include('safety-shop.shared.nav')
<div class="card"><div class="card-body"><div class="form-grid"><div><span class="sub">Customer</span><h2>{{ $return->sale->customer }}</h2>@if($return->sale->customer_phone)<p>{{ $return->sale->customer_phone }}</p>@endif</div><div><span class="sub">Return details</span><p><strong>Original:</strong> SALE-{{ $return->sale_id }}<br><strong>Location:</strong> {{ $return->location->name }}<br><strong>Refund:</strong> SAR {{ \App\Services\Pay::money($return->refund_cents) }} via {{ ucwords(str_replace('_',' ',$return->refund_method)) }}<br><strong>Processed:</strong> {{ $return->created_at->format('d M Y H:i') }} by {{ $return->creator->name }}</p></div></div><p><strong>Reason:</strong> {{ $return->reason }}</p></div></div>
<div class="card"><div class="table-wrap"><table><thead><tr><th>Product</th><th>Barcode</th><th>Returned quantity</th><th>Refund SAR</th></tr></thead><tbody>@foreach($return->lines as $line)<tr><td>{{ $line->saleLine->sku }} · {{ $line->saleLine->name }}</td><td>{{ $line->saleLine->barcode ?: '—' }}</td><td>{{ $line->quantity }} {{ $line->saleLine->unit }}</td><td>{{ \App\Services\Pay::money($line->refund_cents) }}</td></tr>@endforeach</tbody></table></div></div>
@endsection
