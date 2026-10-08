@extends('layouts.app')
@section('title',$product->exists?'Edit safety product':'Add safety product')
@section('eyebrow','Safety shop · Product master')
@section('description','Keep a separate SKU for each size or variant. Stock changes are posted through the movement ledger.')
@section('head')<link rel="stylesheet" href="{{ asset('css/safety-shop/products/form.css') }}?v={{ filemtime(public_path('css/safety-shop/products/form.css')) }}">@endsection
@section('content')
@include('safety-shop.shared.nav')
<div class="card"><div class="card-body"><form id="product-master-form" method="POST" action="{{ $product->exists?route('safety-shop.products.update',$product):route('safety-shop.products.store') }}" data-create-url="{{ route('safety-shop.products.store') }}" data-barcode-url="{{ route('safety-shop.products.barcode-lookup') }}" data-sku-url="{{ route('safety-shop.products.sku-suggestion') }}">@csrf
<input type="hidden" name="company_id" id="product-company-id" value="{{ $companyId }}">
<input id="product-method" type="hidden" name="_method" value="{{ $product->exists?'PUT':'' }}" {{ $product->exists?'':'disabled' }}>
<div id="product-lookup-message" class="notice" hidden role="status"></div>
<div class="form-grid">
@foreach(['barcode'=>'Barcode','sku'=>'SKU','name'=>'Product name','brand'=>'Brand','size'=>'Size / variant','unit'=>'Stock unit','safety_standard'=>'Safety standard / certification'] as $field=>$label)
<div class="field"><label for="{{ $field }}">{{ $label }}{{ in_array($field,['sku','name','unit'])?' *':'' }}</label><input id="{{ $field }}" name="{{ $field }}" value="{{ old($field,$product->$field) }}" {{ in_array($field,['sku','name','unit'])?'required':'' }} maxlength="{{ $field==='barcode'?100:($field==='sku'||$field==='size'?50:($field==='unit'?30:($field==='brand'?100:150))) }}" @if($field==='barcode') autocomplete="off" placeholder="Scan or type barcode, then press Enter" @elseif($field==='sku') autocomplete="off" placeholder="Generated after selecting a category" @else list="product-{{ $field }}-options" autocomplete="off" data-searchable-list @endif>@if(!in_array($field,['barcode','sku']))<datalist id="product-{{ $field }}-options">@foreach($suggestions[$field] as $option)<option value="{{ $option }}"></option>@endforeach</datalist><small>Click to view saved values, or type to search and enter a new one.</small>@elseif($field==='sku')<small>The system generates the next available SKU from the selected category.</small>@else<small>Existing barcodes load the previous product automatically.</small>@endif</div>
@endforeach
@php($selectedCategory=$categories->firstWhere('id',(int)old('category_id',$product->category_id)))
<div class="field"><label for="category_search">Category</label><input id="category_search" list="product-category-options" autocomplete="off" data-searchable-list value="{{ optional($selectedCategory)->name }}" placeholder="Type to search categories"><input id="category_id" name="category_id" type="hidden" value="{{ old('category_id',$product->category_id) }}"><datalist id="product-category-options">@foreach($categories as $category)<option value="{{ $category->name }}" data-id="{{ $category->id }}">{{ $category->is_active?'':'Inactive' }}</option>@endforeach</datalist><small>Select an existing category; leave blank for Uncategorized.</small></div>
<div class="field"><label for="reorder_level">Reorder level *</label><input id="reorder_level" name="reorder_level" type="number" step="1" min="0" max="1000000" required value="{{ old('reorder_level',$product->reorder_level) }}"></div>
<div class="field"><label for="cost">Current unit cost (SAR) *</label><input id="cost" name="cost" type="number" min="0" max="99999999.99" step="0.01" required value="{{ old('cost',number_format($product->cost_cents/100,2,'.','')) }}"></div>
<div class="field"><label for="price">Selling price (SAR) *</label><input id="price" name="price" type="number" min="0" max="99999999.99" step="0.01" required value="{{ old('price',number_format($product->price_cents/100,2,'.','')) }}"></div>
<div class="field"><label for="is_active">Status *</label><select id="is_active" name="is_active"><option value="1" {{ old('is_active',$product->is_active)?'selected':'' }}>Active</option><option value="0" {{ !old('is_active',$product->is_active)?'selected':'' }}>Inactive</option></select></div>
<div class="field"><label for="notes">Notes</label><textarea id="notes" name="notes" maxlength="2000">{{ old('notes',$product->notes) }}</textarea></div>
</div><div class="form-footer"><button class="btn">Save product</button><a class="btn secondary" href="{{ route('safety-shop.index') }}">Cancel</a></div>
</form></div></div>
<script src="{{ asset('js/safety-shop/products/form.js') }}?v={{ filemtime(public_path('js/safety-shop/products/form.js')) }}" defer></script>
@endsection
