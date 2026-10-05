@extends('layouts.app')
@section('title','Barcode checkout')
@section('eyebrow','Safety shop · Sales')
@section('description','Choose the shop location, scan a barcode, and press Enter. Repeated scans increase quantity.')
@section('content')
@include('safety-shop.shared.nav')
@if($locations->isEmpty())<div class="card"><div class="card-body">Add an active shop location before selling.</div></div>@else
<div class="card"><div class="card-body">
<form id="shop-checkout" method="POST" action="{{ route('safety-shop.sales.store') }}" data-barcode-url="{{ route('safety-shop.barcode') }}">@csrf
<input type="hidden" name="request_key" value="{{ $requestKey }}">
<div class="form-grid">
<div class="field"><label for="checkout-location">Shop location *</label><select id="checkout-location" name="location_id" required><option value="">Choose location</option>@foreach($locations as $location)<option value="{{ $location->id }}" {{ old('location_id')==$location->id?'selected':'' }}>{{ $location->name }}</option>@endforeach</select><small>Changing location clears the cart.</small></div>
<div class="field"><label for="checkout-customer">Customer</label><input id="checkout-customer" name="customer" maxlength="150" value="{{ old('customer','Walk-in customer') }}"></div>
<div class="field"><label for="checkout-barcode">Scan / type barcode</label><input id="checkout-barcode" autocomplete="off" maxlength="100" placeholder="Scan barcode, then Enter"><button id="checkout-add" class="btn secondary" type="button">Add scanned product</button></div>
</div><p id="checkout-message" role="status" aria-live="polite"></p>
<div class="table-wrap"><table><thead><tr><th>Product / barcode</th><th>Available</th><th>Unit price SAR</th><th>Quantity</th><th>Total SAR</th><th></th></tr></thead><tbody id="checkout-lines"><tr><td colspan="6">Scan a product to start.</td></tr></tbody></table></div>
<div class="form-grid" style="margin-top:20px">
<div class="field"><label>Total (SAR)</label><strong id="checkout-total">0.00</strong></div>
<div class="field"><label for="checkout-method">Payment method *</label><select id="checkout-method" name="payment_method"><option value="cash" {{ old('payment_method')==='cash'?'selected':'' }}>Cash</option><option value="card" {{ old('payment_method')==='card'?'selected':'' }}>Card</option><option value="bank" {{ old('payment_method')==='bank'?'selected':'' }}>Bank transfer</option></select></div>
<div class="field"><label for="checkout-paid">Amount received (SAR) *</label><input id="checkout-paid" name="paid" type="number" step="0.01" min="0" max="99999999.99" required value="{{ old('paid') }}"><small>For card/bank, enter the exact total.</small></div>
<div class="field"><label>Cash change (SAR)</label><strong id="checkout-change">0.00</strong></div>
</div><p class="help">Prices are the saved product selling prices. This stock sale receipt is separate from tax invoicing. A rejected sale leaves stock unchanged; rescan products to retry.</p>
<div class="form-footer"><button id="checkout-submit" class="btn" type="submit" disabled>Complete sale</button></div>
<noscript><p>Enable JavaScript to use barcode checkout.</p></noscript>
</form></div></div>
<script src="{{ asset('js/safety-shop/sales/checkout.js') }}" defer></script>
@endif
@endsection
