@extends('layouts.app')
@section('title','Quotation settings')
@section('eyebrow','Safety shop · Settings')
@section('description','Set the default validity period and Terms & Conditions for new quotations.')
@section('actions')<a class="btn secondary" href="{{ route('quotations.index') }}">View quotations</a>@endsection
@section('content')
<div class="card"><div class="card-head"><div><h2>{{ $company->name }}</h2><span class="sub">Defaults apply to new quotations created from either the quotation form or POS checkout.</span></div><span class="badge">Company admin</span></div>
<div class="card-body"><form method="POST" action="{{ route('safety-shop.settings.quotations.update') }}">@csrf @method('PUT')
<div class="form-grid"><div class="field"><label for="quotation-valid-days">Default validity (days) *</label><input id="quotation-valid-days" name="quotation_valid_days" type="number" min="1" max="365" required value="{{ old('quotation_valid_days',$settings->quotation_valid_days ?: 30) }}"><span class="help">The valid-until date is calculated from the quotation date.</span></div>
<div class="field full"><label for="quotation-terms">Default Terms &amp; Conditions</label><textarea id="quotation-terms" name="quotation_terms" maxlength="5000" rows="12" placeholder="Prices are valid for 30 days.&#10;Delivery is subject to stock availability.&#10;Payment is due within 30 days.">{{ old('quotation_terms',$settings->quotation_terms) }}</textarea><span class="help">Enter one condition per line. Each non-empty line becomes a bullet in the quotation PDF. You can still edit the text on an individual quotation.</span></div></div>
<div class="form-footer"><button class="btn" type="submit">Save quotation settings</button></div></form></div></div>
@endsection
