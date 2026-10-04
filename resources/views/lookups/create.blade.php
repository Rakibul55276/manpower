@extends('layouts.app')
@section('title', 'Add '.($type === 'companies' ? 'company' : 'designation'))
@section('eyebrow', 'Organization settings')
@section('description', $type === 'companies' ? 'Create a company master record.' : 'Create a designation for employee profiles.')
@section('content')
<div class="card"><div class="card-head"><h2>New {{ $type === 'companies' ? 'company' : 'designation' }}</h2></div><div class="card-body"><form method="POST" action="{{ route($type.'.store') }}">@csrf
<div class="form-grid"><div class="field"><label>Name *</label><input name="name" required maxlength="150" value="{{ old('name') }}" autofocus></div>
@if($type === 'companies')<div class="field"><label>Location *</label><input name="location" required maxlength="150" value="{{ old('location') }}" placeholder="City, Country"></div><div class="field"><label>Registration number</label><input name="registration_number" maxlength="100" value="{{ old('registration_number') }}"></div><div class="field"><label>Contact person</label><input name="contact_person" maxlength="150" value="{{ old('contact_person') }}"></div><div class="field"><label>Phone</label><input name="phone" maxlength="50" value="{{ old('phone') }}"></div><div class="field"><label>Email</label><input type="email" name="email" maxlength="150" value="{{ old('email') }}"></div><div class="field full"><label>Address</label><textarea name="address" maxlength="500">{{ old('address') }}</textarea></div><div class="field full"><label>Notes</label><textarea name="notes" maxlength="1000">{{ old('notes') }}</textarea></div>@endif</div>
<div class="form-footer"><a class="btn secondary" href="{{ route($type.'.index') }}">Cancel</a><button class="btn">Create {{ $type === 'companies' ? 'company' : 'designation' }} →</button></div></form></div></div>
@endsection
