@extends('layouts.app')
@section('title', 'Edit '.($type === 'companies' ? 'company' : 'designation'))
@section('eyebrow', 'Organization settings')
@section('content')
<div class="card"><div class="card-head"><h2>{{ $item->name }}</h2></div><div class="card-body"><form method="POST" action="{{ route($type.'.update', $item->id) }}" @if($type==='companies') enctype="multipart/form-data" @endif>@csrf @method('PUT')
<div class="form-grid"><div class="field"><label>Name *</label><input name="name" value="{{ old('name', $item->name) }}" required maxlength="150"></div>
@if($type === 'companies')
<div class="field"><label>Company logo</label>@if($item->logo_path)<img src="{{ route('companies.logo',$item) }}" alt="{{ $item->name }} logo" style="display:block;max-width:180px;max-height:90px;object-fit:contain;margin:0 0 10px"><label style="font-weight:400"><input type="checkbox" name="remove_logo" value="1"> Remove current logo</label>@endif<input type="file" name="logo" accept="image/jpeg,image/png,image/webp"><span class="help">Uploading a new logo replaces the current one. Maximum 2 MB.</span></div>
@if(config('saas.saas_voucher_enabled'))<div class="field"><label>Company code *</label><input name="company_code" value="{{ old('company_code', $item->company_code) }}" required maxlength="30" style="text-transform:uppercase"></div>@endif
<div class="field"><label>Location *</label><input name="location" value="{{ old('location', $item->location) }}" required maxlength="150"></div>
<div class="field"><label>Registration number</label><input name="registration_number" value="{{ old('registration_number', $item->registration_number) }}" maxlength="100"></div><div class="field"><label>Contact person</label><input name="contact_person" value="{{ old('contact_person', $item->contact_person) }}" maxlength="150"></div><div class="field"><label>Phone</label><input name="phone" value="{{ old('phone', $item->phone) }}" maxlength="50"></div><div class="field"><label>Email</label><input type="email" name="email" value="{{ old('email', $item->email) }}" maxlength="150"></div><div class="field full"><label>Address</label><textarea name="address" maxlength="500">{{ old('address', $item->address) }}</textarea></div><div class="field full"><label>Notes</label><textarea name="notes" maxlength="1000">{{ old('notes', $item->notes) }}</textarea></div>
@endif
<div class="field"><label>Status *</label><select name="is_active"><option value="1" {{ old('is_active', $item->is_active) ? 'selected' : '' }}>Active</option><option value="0" {{ !old('is_active', $item->is_active) ? 'selected' : '' }}>Inactive</option></select></div></div>
<div class="form-footer"><a class="btn secondary" href="{{ route($type.'.index') }}">Cancel</a><button class="btn">Save changes</button></div></form></div></div>
@endsection
