@extends('layouts.app')
@section('title', 'Edit '.($type === 'companies' ? 'company' : 'designation'))
@section('eyebrow', 'Organization settings')
@section('content')
<div class="card"><div class="card-head"><h2>{{ $item->name }}</h2></div><div class="card-body"><form method="POST" action="{{ route($type.'.update', $item->id) }}">@csrf @method('PUT')
<div class="form-grid"><div class="field"><label>Name *</label><input name="name" value="{{ old('name', $item->name) }}" required maxlength="150"></div>
@if($type === 'companies')
<div class="field"><label>Location *</label><input name="location" value="{{ old('location', $item->location) }}" required maxlength="150"></div>
<div class="field"><label>Registration number</label><input name="registration_number" value="{{ old('registration_number', $item->registration_number) }}" maxlength="100"></div><div class="field"><label>Contact person</label><input name="contact_person" value="{{ old('contact_person', $item->contact_person) }}" maxlength="150"></div><div class="field"><label>Phone</label><input name="phone" value="{{ old('phone', $item->phone) }}" maxlength="50"></div><div class="field"><label>Email</label><input type="email" name="email" value="{{ old('email', $item->email) }}" maxlength="150"></div><div class="field full"><label>Address</label><textarea name="address" maxlength="500">{{ old('address', $item->address) }}</textarea></div><div class="field full"><label>Notes</label><textarea name="notes" maxlength="1000">{{ old('notes', $item->notes) }}</textarea></div>
@endif
<div class="field"><label>Status *</label><select name="is_active"><option value="1" {{ old('is_active', $item->is_active) ? 'selected' : '' }}>Active</option><option value="0" {{ !old('is_active', $item->is_active) ? 'selected' : '' }}>Inactive</option></select></div></div>
<div class="form-footer"><a class="btn secondary" href="{{ route($type.'.index') }}">Cancel</a><button class="btn">Save changes</button></div></form></div></div>
@endsection
