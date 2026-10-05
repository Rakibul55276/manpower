@extends('layouts.app')
@section('title',($editing->exists?'Edit ':'Add ').$type)
@section('eyebrow','Safety shop · '.ucfirst($type))
@section('description','Maintain this '.$type.' record independently.')
@section('actions')<a class="btn secondary" href="{{ route('safety-shop.'.$directory.'.index') }}">Back to directory</a>@endsection
@section('content')
@include('safety-shop.shared.nav')
@if(auth()->user()->canApprove())
<div class="card"><div class="card-head"><h2>{{ $editing->exists?'Edit':'Add' }} {{ $type }}</h2></div><div class="card-body"><form method="POST" action="{{ $editing->exists?route('safety-shop.'.$directory.'.update',$editing):route('safety-shop.'.$directory.'.store') }}">@csrf @if($editing->exists)
@method('PUT')
@endif
<input type="hidden" name="type" value="{{ $type }}"><div class="form-grid">
<div class="field"><label for="name">Name *</label><input id="name" name="name" required maxlength="150" value="{{ old('name',$editing->name) }}"></div>
@if($type==='supplier')
<div class="field"><label for="phone">Phone</label><input id="phone" name="phone" maxlength="50" value="{{ old('phone',$editing->phone) }}"></div>
<div class="field"><label for="email">Email</label><input id="email" name="email" type="email" maxlength="150" value="{{ old('email',$editing->email) }}"></div>
@endif
@if($type!=='category')<div class="field"><label for="address">Address / location details</label><input id="address" name="address" maxlength="500" value="{{ old('address',$editing->address) }}"></div>@endif
<div class="field"><label for="is_active">Status *</label><select id="is_active" name="is_active"><option value="1" {{ old('is_active',$editing->is_active)?'selected':'' }}>Active</option><option value="0" {{ !old('is_active',$editing->is_active)?'selected':'' }}>Inactive</option></select></div>
</div><div class="form-footer"><button class="btn">Save {{ $type }}</button>@if($editing->exists)<a class="btn secondary" href="{{ route('safety-shop.'.$directory.'.index') }}">Cancel edit</a>@endif</div></form></div></div>
@endif

@endsection
