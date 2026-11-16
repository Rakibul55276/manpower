@extends('layouts.app')
@section('title','Quotation settings')
@section('eyebrow','Safety shop · Settings')
@section('description','Set the default validity period and Terms & Conditions for new quotations.')
@section('actions')<a class="btn secondary" href="{{ route('quotations.index') }}">View quotations</a>@endsection
@section('content')
@php
$quotationTermsValue=old('quotation_terms',$settings->effectiveQuotationTerms());
$quotationTermRows=collect(preg_split('/\R/u',(string)$quotationTermsValue))->map(fn($term)=>trim($term))->filter()->values();
if($quotationTermRows->isEmpty())$quotationTermRows=collect(['']);
@endphp
<style>
.quotation-terms-list{display:grid;gap:8px}.quotation-term-row{display:grid;grid-template-columns:minmax(0,1fr) 40px 40px;gap:7px;align-items:center}.quotation-term-row input{min-width:0}.quotation-term-action{height:40px;min-width:40px;padding:0;display:grid;place-items:center;font-size:18px}.quotation-terms-add{margin-top:10px}@media(max-width:560px){.quotation-term-row{grid-template-columns:minmax(0,1fr) 38px 38px}.quotation-term-action{height:38px;min-width:38px}}
</style>
<div class="card"><div class="card-head"><div><h2>{{ $company->name }}</h2><span class="sub">Defaults apply to new quotations created from either the quotation form or POS checkout.</span></div><span class="badge">Company admin</span></div>
<div class="card-body"><form id="quotation-settings-form" method="POST" action="{{ route('safety-shop.settings.quotations.update') }}">@csrf @method('PUT')
<div class="form-grid"><div class="field"><label for="quotation-valid-days">Default validity (days) *</label><input id="quotation-valid-days" name="quotation_valid_days" type="number" min="1" max="365" required value="{{ old('quotation_valid_days',$settings->quotation_valid_days ?: 30) }}"><span class="help">The valid-until date is calculated from the quotation date.</span></div>
<div class="field full"><label>Default Terms &amp; Conditions</label><div id="quotation-terms-list" class="quotation-terms-list">@foreach($quotationTermRows as $term)<div class="quotation-term-row"><input class="quotation-term-input" type="text" maxlength="1000" value="{{ $term }}" placeholder="Enter one condition"><button class="btn secondary quotation-term-action quotation-term-add-row" type="button" aria-label="Add condition after this row" title="Add condition">+</button><button class="btn danger quotation-term-action quotation-term-remove-row" type="button" aria-label="Remove condition" title="Remove condition">−</button></div>@endforeach</div><button id="quotation-terms-add" class="btn secondary small quotation-terms-add" type="button">+ Add condition</button><textarea id="quotation-terms" name="quotation_terms" maxlength="5000" hidden>{{ $quotationTermsValue }}</textarea><span class="help">Add each condition as a separate item. Every item becomes one bullet in the quotation PDF and can still be edited on an individual quotation.</span></div></div>
<div class="form-footer"><button class="btn" type="submit">Save quotation settings</button></div></form></div></div>
<template id="quotation-term-template"><div class="quotation-term-row"><input class="quotation-term-input" type="text" maxlength="1000" placeholder="Enter one condition"><button class="btn secondary quotation-term-action quotation-term-add-row" type="button" aria-label="Add condition after this row" title="Add condition">+</button><button class="btn danger quotation-term-action quotation-term-remove-row" type="button" aria-label="Remove condition" title="Remove condition">−</button></div></template>
<script>
(()=>{const form=document.getElementById('quotation-settings-form'),list=document.getElementById('quotation-terms-list'),output=document.getElementById('quotation-terms'),template=document.getElementById('quotation-term-template');if(!form||!list||!output||!template)return;const sync=()=>{output.value=Array.from(list.querySelectorAll('.quotation-term-input')).map(input=>input.value.trim()).filter(Boolean).join('\n');};const add=(after=null)=>{const row=template.content.firstElementChild.cloneNode(true);after?after.after(row):list.appendChild(row);row.querySelector('.quotation-term-input').focus();sync();};list.addEventListener('click',event=>{const row=event.target.closest('.quotation-term-row');if(!row)return;if(event.target.closest('.quotation-term-add-row'))add(row);if(event.target.closest('.quotation-term-remove-row')){if(list.children.length===1){row.querySelector('.quotation-term-input').value='';row.querySelector('.quotation-term-input').focus();}else row.remove();sync();}});list.addEventListener('input',sync);document.getElementById('quotation-terms-add').addEventListener('click',()=>add());form.addEventListener('submit',sync);sync();})();
</script>
@endsection
