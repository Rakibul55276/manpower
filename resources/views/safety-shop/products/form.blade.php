@extends('layouts.app')
@section('title',$product->exists?'Edit safety product':'Add safety product')
@section('eyebrow','Safety shop · Product master')
@section('description','Keep a separate SKU for each size or variant. Stock changes are posted through the movement ledger.')
@section('content')
@include('safety-shop.shared.nav')
<div class="card"><div class="card-body"><form method="POST" action="{{ $product->exists?route('safety-shop.products.update',$product):route('safety-shop.products.store') }}">@csrf @if($product->exists)
@method('PUT')
@endif
<div class="form-grid">
@foreach(['barcode'=>'Barcode','sku'=>'SKU','name'=>'Product name','brand'=>'Brand','size'=>'Size / variant','unit'=>'Stock unit','safety_standard'=>'Safety standard / certification'] as $field=>$label)
<div class="field"><label for="{{ $field }}">{{ $label }}{{ in_array($field,['sku','name','unit'])?' *':'' }}</label><input id="{{ $field }}" name="{{ $field }}" value="{{ old($field,$product->$field) }}" {{ in_array($field,['sku','name','unit'])?'required':'' }} maxlength="{{ $field==='barcode'?100:($field==='sku'||$field==='size'?50:($field==='unit'?30:($field==='brand'?100:150))) }}"></div>
@endforeach
<div class="field"><label for="category_id">Category</label><select id="category_id" name="category_id"><option value="">Uncategorized</option>@foreach($categories as $category)<option value="{{ $category->id }}" {{ old('category_id',$product->category_id)==$category->id?'selected':'' }}>{{ $category->name }}{{ $category->is_active?'':' (inactive)' }}</option>@endforeach</select></div>
<div class="field"><label for="reorder_level">Reorder level *</label><input id="reorder_level" name="reorder_level" type="number" step="1" min="0" max="1000000" required value="{{ old('reorder_level',$product->reorder_level) }}"></div>
<div class="field"><label for="cost">Current unit cost (SAR) *</label><input id="cost" name="cost" type="number" min="0" max="99999999.99" step="0.01" required value="{{ old('cost',number_format($product->cost_cents/100,2,'.','')) }}"></div>
<div class="field"><label for="price">Selling price (SAR) *</label><input id="price" name="price" type="number" min="0" max="99999999.99" step="0.01" required value="{{ old('price',number_format($product->price_cents/100,2,'.','')) }}"></div>
<div class="field"><label for="is_active">Status *</label><select id="is_active" name="is_active"><option value="1" {{ old('is_active',$product->is_active)?'selected':'' }}>Active</option><option value="0" {{ !old('is_active',$product->is_active)?'selected':'' }}>Inactive</option></select></div>
<div class="field"><label for="notes">Notes</label><textarea id="notes" name="notes" maxlength="2000">{{ old('notes',$product->notes) }}</textarea></div>
</div><div class="form-footer"><button class="btn">Save product</button><a class="btn secondary" href="{{ route('safety-shop.index') }}">Cancel</a></div>
</form></div></div>
@endsection
