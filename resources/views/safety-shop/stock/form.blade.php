@extends('layouts.app')
@section('title','Post stock movement')
@section('eyebrow','Safety shop · Stock control')
@section('description','Receive supplier stock or transfer stock between locations. Sales and returns are recorded in their dedicated workflows.')
@section('content')
@include('safety-shop.shared.nav')
@if($products->isEmpty() || $locations->isEmpty())<div class="card"><div class="card-body"><p>Add at least one active product and one active location before posting stock.</p></div></div>@else
<div class="card"><div class="card-body"><form method="POST" action="{{ route('safety-shop.stock.store') }}" enctype="multipart/form-data">@csrf
<input type="hidden" name="request_key" value="{{ $requestKey }}">
<input type="hidden" name="company_id" value="{{ $companyId }}">
<div class="form-grid">
<div class="field"><label for="type">Movement type *</label><select id="type" name="type">@foreach(['receipt'=>'Stock receipt / opening stock','transfer'=>'Transfer between locations'] as $value=>$label)<option value="{{ $value }}" {{ old('type')===$value?'selected':'' }}>{{ $label }}</option>@endforeach</select><small>Sales are issued through Barcode Checkout. Returns are processed from Customer Returns.</small></div>
<div class="field"><label for="stock_barcode">Product barcode</label><input id="stock_barcode" type="text" inputmode="text" autocomplete="off" maxlength="100" placeholder="Scan or type barcode, then press Enter"><small id="stock-barcode-message">Scanning selects the existing product automatically.</small></div>
<div class="field"><label for="product_id">Product *</label><select id="product_id" name="product_id" required><option value="">Choose product</option>@foreach($products as $product)<option value="{{ $product->id }}" data-barcode="{{ $product->barcode }}" {{ old('product_id',request('product_id'))==$product->id?'selected':'' }}>{{ $product->sku }} · {{ $product->name }}{{ $product->size?' · '.$product->size:'' }} ({{ $product->unit }})</option>@endforeach</select><small>You can also select the existing product manually.</small></div>
<div class="field"><label id="location-label" for="location_id">Stock location *</label><select id="location_id" name="location_id" required><option value="">Choose location</option>@foreach($locations as $location)<option value="{{ $location->id }}" {{ old('location_id')==$location->id?'selected':'' }}>{{ $location->name }}</option>@endforeach</select></div>
<div class="field" id="destination-field"><label for="destination_id">Destination *</label><select id="destination_id" name="destination_id"><option value="">Choose destination</option>@foreach($locations as $location)<option value="{{ $location->id }}" {{ old('destination_id')==$location->id?'selected':'' }}>{{ $location->name }}</option>@endforeach</select></div>
<div class="field"><label for="quantity">Quantity *</label><input id="quantity" name="quantity" type="number" step="1" min="1" max="1000000" required value="{{ old('quantity') }}"><small>Enter a positive whole-unit quantity.</small></div>
<div class="field"><label for="movement_date">Movement date *</label><input id="movement_date" name="movement_date" type="date" required max="{{ now()->format('Y-m-d') }}" value="{{ old('movement_date',now()->format('Y-m-d')) }}"></div>
<div class="field"><label for="supplier_id">Supplier (optional)</label><select id="supplier_id" name="supplier_id"><option value="">No supplier</option>@foreach($suppliers as $supplier)<option value="{{ $supplier->id }}" {{ old('supplier_id')==$supplier->id?'selected':'' }}>{{ $supplier->name }}</option>@endforeach</select></div>
<div class="field"><label for="reference">Supplier document / transfer reference</label><input id="reference" name="reference" maxlength="100" value="{{ old('reference') }}"></div>
<div class="field"><label id="supporting-document-label" for="supporting_document">Supporting receipt <span class="muted">(optional)</span></label><input id="supporting_document" name="supporting_document" type="file" accept=".pdf,.jpg,.jpeg,.png,.webp"><small>PDF, JPG, PNG, or WebP; maximum 10 MB. Stored privately.</small></div>
<div class="field"><label for="notes">Receipt / transfer details *</label><textarea id="notes" name="notes" required maxlength="2000">{{ old('notes') }}</textarea></div>
</div><p class="help">Posted movements remain in the ledger. Correct an entry by posting a compensating movement with the original movement number in its reference. Ledger balances follow posting order; the movement date is the document date.</p>
<div class="form-footer"><button class="btn">Post stock movement</button><a class="btn secondary" href="{{ route('safety-shop.stock.index') }}">Cancel</a></div>
</form></div></div>@endif
<script src="{{ asset('js/safety-shop/stock/form.js') }}?v={{ filemtime(public_path('js/safety-shop/stock/form.js')) }}" defer></script>
@endsection
