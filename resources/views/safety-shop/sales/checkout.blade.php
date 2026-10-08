@extends('layouts.app')
@section('title','Barcode checkout')
@section('eyebrow','Safety shop · Sales')
@section('description','Choose the shop location, scan a barcode, and press Enter. Repeated scans increase quantity.')
@section('content')
@include('safety-shop.shared.nav')
@if(!$shopLocation)<div class="card"><div class="card-body">Configure an active default shop location in Document Branding before selling.</div></div>@else
<div class="card"><div class="card-body">
<form id="shop-checkout" method="POST" action="{{ route('safety-shop.sales.store') }}" data-barcode-url="{{ route('safety-shop.barcode') }}">@csrf
<input type="hidden" name="request_key" value="{{ $requestKey }}">
<div class="form-grid">
<div class="field"><label>Shop location</label><div><strong>{{ $shopLocation->name }}</strong></div><input id="checkout-location" name="location_id" type="hidden" value="{{ $shopLocation->id }}"></div>
<div class="field full"><label for="checkout-saved-customer">Saved customer <span class="muted">(optional)</span></label><select id="checkout-saved-customer" name="customer_id"><option value="">Walk-in sale — no customer details on receipt</option>@foreach($customers as $savedCustomer)<option value="{{ $savedCustomer->id }}" data-type="{{ $savedCustomer->customer_type }}" data-name="{{ $savedCustomer->name }}" data-contact="{{ $savedCustomer->contact_person }}" data-phone="{{ $savedCustomer->phone }}" data-email="{{ $savedCustomer->email }}" data-vat="{{ $savedCustomer->vat_number }}" data-cr="{{ $savedCustomer->commercial_registration }}" data-address="{{ $savedCustomer->address }}" {{ old('customer_id')==$savedCustomer->id?'selected':'' }}>{{ $savedCustomer->customer_type==='company'?'Company':'Retail' }} · {{ $savedCustomer->name }}{{ $savedCustomer->phone?' · '.$savedCustomer->phone:'' }}</option>@endforeach</select><small>Select an approved customer, or leave this blank for a clean walk-in receipt.</small><button id="checkout-new-customer-toggle" class="btn link" type="button" aria-expanded="false" aria-controls="checkout-new-customer">+ Quick add retail customer</button></div>
<div id="checkout-new-customer" class="form-grid full" hidden><div class="field"><label for="checkout-customer">Retail customer name</label><input id="checkout-customer" name="customer" maxlength="150" value="{{ old('customer') }}" placeholder="Enter customer name"></div><div class="field"><label for="checkout-customer-phone">Mobile <span class="muted">(optional)</span></label><input id="checkout-customer-phone" name="customer_phone" type="tel" maxlength="30" autocomplete="tel" value="{{ old('customer_phone') }}" placeholder="e.g. +966 55 000 0000"></div><div class="field full"><small>Company purchasers must be created and approved in Customer Master before they can be selected here.</small></div></div>
<input id="checkout-customer-type" name="customer_type" type="hidden" value="retail"><input id="checkout-customer-contact" name="customer_contact_person" type="hidden" value=""><input id="checkout-customer-email" name="customer_email" type="hidden" value=""><input id="checkout-customer-vat" name="customer_vat_number" type="hidden" value=""><input id="checkout-customer-cr" name="customer_commercial_registration" type="hidden" value=""><input id="checkout-customer-address" name="customer_address" type="hidden" value="">
<div class="field"><label for="checkout-barcode">Scan / type barcode</label><input id="checkout-barcode" autocomplete="off" maxlength="100" placeholder="Scan barcode, then Enter"><button id="checkout-add" class="btn secondary" type="button">Add scanned product</button></div>
</div><p id="checkout-message" role="status" aria-live="polite"></p>
<div class="table-wrap"><table><thead><tr><th>Product / barcode</th><th>Available</th><th>Unit price SAR</th><th>Quantity</th><th>Total SAR</th><th></th></tr></thead><tbody id="checkout-lines"><tr><td colspan="6">Scan a product to start.</td></tr></tbody></table></div>
<div class="form-grid" style="margin-top:20px">
<div class="field"><label>Subtotal (SAR)</label><strong id="checkout-subtotal">0.00</strong></div>
<div class="field"><label for="checkout-discount">Discount (SAR)</label><input id="checkout-discount" name="discount" type="number" step="0.01" min="0" max="99999999.99" value="{{ old('discount','0.00') }}"></div>
<div class="field"><label for="checkout-tax-rate">VAT rate (%)</label><input id="checkout-tax-rate" name="tax_rate" type="number" step="0.01" min="0" max="100" value="{{ old('tax_rate','15.00') }}"><small>Standard Saudi VAT is 15%. Use 0 for zero-rated or exempt sales.</small></div>
<div class="field"><label>VAT amount (SAR)</label><strong id="checkout-tax">0.00</strong></div>
<div class="field"><label>Final total (SAR)</label><strong id="checkout-total">0.00</strong></div>
<input id="checkout-cash" name="cash_paid" type="hidden" value="{{ old('cash_paid','0.00') }}">
<input id="checkout-card" name="card_paid" type="hidden" value="{{ old('card_paid','0.00') }}">
<input id="checkout-bank" name="bank_paid" type="hidden" value="{{ old('bank_paid','0.00') }}">
<div class="field" style="grid-column:1/-1"><label>Payment methods *</label><div id="checkout-payment-rows" data-cash="{{ old('cash_paid','0.00') }}" data-card="{{ old('card_paid','0.00') }}" data-bank="{{ old('bank_paid','0.00') }}"></div><small>Choose a method and enter its amount. If a balance remains, another payment row opens automatically.</small></div>
<div class="field"><label>Combined payment (SAR)</label><strong id="checkout-paid-total">0.00</strong></div>
<div class="field"><label>Remaining balance (SAR)</label><strong id="checkout-balance">0.00</strong></div>
<div class="field"><label>Cash change (SAR)</label><strong id="checkout-change">0.00</strong></div>
</div><p class="help">Prices are the saved product selling prices. This stock sale receipt is separate from tax invoicing. A rejected sale leaves stock unchanged; rescan products to retry.</p>
<div class="form-footer"><button id="checkout-submit" class="btn" type="submit" disabled>Complete sale</button></div>
<noscript><p>Enable JavaScript to use barcode checkout.</p></noscript>
</form></div></div>
<script src="{{ asset('js/safety-shop/sales/checkout.js') }}" defer></script>
@endif
@endsection
