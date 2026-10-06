@extends('layouts.app')
@section('title',($customer->exists?'Edit':'Add').' customer')
@section('eyebrow','Safety shop · Customer master')
@section('description','Customer details are reused during checkout while each receipt keeps its original snapshot.')
@section('actions')<a class="btn secondary" href="{{ route('safety-shop.customers.index') }}">Back to customers</a>@endsection
@section('content')
@include('safety-shop.shared.nav')
<div class="card"><div class="card-body"><form method="POST" action="{{ $customer->exists?route('safety-shop.customers.update',$customer):route('safety-shop.customers.store') }}">@csrf @if($customer->exists)@method('PUT')@endif
<div class="form-grid"><div class="field"><label for="name">Customer name *</label><input id="name" name="name" required maxlength="150" value="{{ old('name',$customer->name) }}"></div><div class="field"><label for="phone">Mobile</label><input id="phone" name="phone" maxlength="30" value="{{ old('phone',$customer->phone) }}" placeholder="+966550000000"><small>Used to match repeat customers.</small></div><div class="field"><label for="email">Email</label><input id="email" name="email" type="email" maxlength="150" value="{{ old('email',$customer->email) }}"></div><div class="field"><label for="address">Address</label><input id="address" name="address" maxlength="500" value="{{ old('address',$customer->address) }}"></div><div class="field"><label for="is_active">Status *</label><select id="is_active" name="is_active"><option value="1" {{ old('is_active',$customer->is_active)?'selected':'' }}>Active</option><option value="0" {{ !old('is_active',$customer->is_active)?'selected':'' }}>Inactive</option></select><small>Inactive customers remain in history but are hidden from checkout.</small></div></div>
<div class="form-footer"><button class="btn">Save customer</button><a class="btn secondary" href="{{ route('safety-shop.customers.index') }}">Cancel</a></div></form></div></div>
@endsection
