@extends('layouts.app')
@section('title','Tax debit notes')
@section('eyebrow','Safety Shop · Financial adjustments')
@section('description','Review invoice-linked debit notes issued for underbilling or approved price adjustments.')
@section('actions')@if(auth()->user()->canApprove())<a class="btn" href="{{ route('safety-shop.debit-notes.create') }}">Create debit note</a>@endif@endsection
@section('content')
@include('safety-shop.shared.nav')
<div class="card"><div class="table-wrap"><table><thead><tr><th>Debit note</th><th>Original invoice</th><th>Customer</th><th>Issue date</th><th>Total SAR</th><th>Created by</th></tr></thead><tbody>@forelse($notes as $note)<tr><td><a href="{{ route('safety-shop.debit-notes.show',$note) }}"><strong>{{ $note->debit_note_number }}</strong></a></td><td><a href="{{ route('safety-shop.sales.show',$note->sale) }}">{{ $note->sale->invoice_number }}</a></td><td>{{ $note->sale->customer }}</td><td>{{ $note->issue_date->format('d M Y') }}</td><td>{{ \App\Services\Pay::money($note->total_cents) }}</td><td>{{ $note->creator->name }}</td></tr>@empty<tr><td colspan="6"><div class="empty">No tax debit notes have been issued.</div></td></tr>@endforelse</tbody></table></div>@include('partials.pagination',['items'=>$notes])</div>
@endsection
