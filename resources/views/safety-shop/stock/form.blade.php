@extends('layouts.app')
@section('title','Post stock movement')
@section('eyebrow','Safety shop · Stock control')
@section('description','Record receipts, issues/sales, customer returns, adjustments, and transfers between locations.')
@section('content')
@include('safety-shop.shared.nav')
@if($products->isEmpty() || $locations->isEmpty())<div class="card"><div class="card-body"><p>Add at least one active product and one active location before posting stock.</p></div></div>@else
<div class="card"><div class="card-body"><form method="POST" action="{{ route('safety-shop.stock.store') }}">@csrf
<input type="hidden" name="request_key" value="{{ $requestKey }}">
<div class="form-grid">
<div class="field"><label for="type">Movement type *</label><select id="type" name="type">@foreach(['receipt'=>'Receipt / opening stock','issue'=>'Issue / sale','return'=>'Customer / unused return','adjustment'=>'Stock adjustment (+ or −)','transfer'=>'Location transfer'] as $value=>$label)<option value="{{ $value }}" {{ old('type')===$value?'selected':'' }}>{{ $label }}</option>@endforeach</select></div>
<div class="field"><label for="product_id">Product *</label><select id="product_id" name="product_id" required><option value="">Choose product</option>@foreach($products as $product)<option value="{{ $product->id }}" {{ old('product_id',request('product_id'))==$product->id?'selected':'' }}>{{ $product->sku }} · {{ $product->name }}{{ $product->size?' · '.$product->size:'' }} ({{ $product->unit }})</option>@endforeach</select></div>
<div class="field"><label for="location_id">Stock location / transfer source *</label><select id="location_id" name="location_id" required><option value="">Choose location</option>@foreach($locations as $location)<option value="{{ $location->id }}" {{ old('location_id')==$location->id?'selected':'' }}>{{ $location->name }}</option>@endforeach</select></div>
<div class="field"><label for="destination_id">Destination (transfers only)</label><select id="destination_id" name="destination_id"><option value="">Choose destination</option>@foreach($locations as $location)<option value="{{ $location->id }}" {{ old('destination_id')==$location->id?'selected':'' }}>{{ $location->name }}</option>@endforeach</select></div>
<div class="field"><label for="quantity">Quantity *</label><input id="quantity" name="quantity" type="number" step="1" min="-1000000" max="1000000" required value="{{ old('quantity') }}"><small>Whole units. Use a negative quantity only to reduce stock through an adjustment.</small></div>
<div class="field"><label for="movement_date">Movement date *</label><input id="movement_date" name="movement_date" type="date" required max="{{ now()->format('Y-m-d') }}" value="{{ old('movement_date',now()->format('Y-m-d')) }}"></div>
<div class="field"><label for="supplier_id">Supplier (optional)</label><select id="supplier_id" name="supplier_id"><option value="">No supplier</option>@foreach($suppliers as $supplier)<option value="{{ $supplier->id }}" {{ old('supplier_id')==$supplier->id?'selected':'' }}>{{ $supplier->name }}</option>@endforeach</select></div>
<div class="field"><label for="recipient">Customer / recipient (required for issues)</label><input id="recipient" name="recipient" list="recipient-options" maxlength="150" autocomplete="off" placeholder="Select or type a customer" value="{{ old('recipient') }}"><datalist id="recipient-options">@foreach($recipients as $recipient)<option value="{{ $recipient }}"></option>@endforeach</datalist><small>Select a previous customer or type a new recipient.</small></div>
<div class="field"><label for="reference">Document / return reference</label><input id="reference" name="reference" maxlength="100" value="{{ old('reference') }}"></div>
<div class="field"><label for="notes">Reason / details *</label><textarea id="notes" name="notes" required maxlength="2000">{{ old('notes') }}</textarea></div>
</div><p class="help">Posted movements remain in the ledger. Correct an entry by posting a compensating movement with the original movement number in its reference. Ledger balances follow posting order; the movement date is the document date.</p>
<div class="form-footer"><button class="btn">Post stock movement</button><a class="btn secondary" href="{{ route('safety-shop.index') }}">Cancel</a></div>
</form></div></div>@endif
@endsection
