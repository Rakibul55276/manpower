@extends('layouts.app')
@section('title',$document->exists?'Edit document details':'Upload scanned document')
@section('eyebrow','Document library')
@section('description',$document->exists?'Update searchable information without replacing the original scan.':'Files are stored privately and delivered only through authenticated access.')
@section('actions')<a class="btn secondary" href="{{ route('document-library.index') }}">← Document library</a>@endsection
@section('content')
<div class="card"><div class="card-body"><form method="POST" enctype="multipart/form-data" action="{{ $document->exists?route('document-library.update',$document):route('document-library.store') }}">@csrf @if($document->exists)@method('PUT')@endif
<div class="form-grid">
@if(auth()->user()->isSuperAdmin())<div class="field"><label for="owner_id">Document owner *</label><select id="owner_id" name="owner_id" required>@foreach($users as $user)<option value="{{ $user->id }}" {{ old('owner_id',$document->owner_id?:auth()->id())==$user->id?'selected':'' }}>{{ $user->name }} · {{ $user->username }}</option>@endforeach</select></div>@endif
<div class="field"><label for="title">Document title *</label><input id="title" name="title" required maxlength="180" value="{{ old('title',$document->title) }}" placeholder="e.g. Passport scan"></div>
<div class="field"><label for="category">Category</label><input id="category" name="category" maxlength="100" value="{{ old('category',$document->category) }}" placeholder="Passport, Certificate, Contract..."></div>
<div class="field"><label for="document_number">Document number</label><input id="document_number" name="document_number" maxlength="100" value="{{ old('document_number',$document->document_number) }}"></div>
<div class="field"><label for="issued_on">Issue date</label><input id="issued_on" type="date" name="issued_on" max="{{ now()->format('Y-m-d') }}" value="{{ old('issued_on',optional($document->issued_on)->format('Y-m-d')) }}"></div>
<div class="field"><label for="expires_on">Expiry date</label><input id="expires_on" type="date" name="expires_on" value="{{ old('expires_on',optional($document->expires_on)->format('Y-m-d')) }}"></div>
@if(!$document->exists)<div class="field full"><label for="scan">Scanned file *</label><input id="scan" type="file" name="scan" accept=".pdf,.jpg,.jpeg,.png,.webp" required><span class="help">PDF, JPG, PNG, or WebP. Maximum 20 MB. Stored outside the public web directory.</span></div>@else<div class="field full"><label>Original file</label><div class="notice info">{{ $document->original_name }} · {{ number_format($document->size_bytes/1024,1) }} KB</div></div>@endif
<div class="field full"><label for="notes">Notes</label><textarea id="notes" name="notes" maxlength="2000">{{ old('notes',$document->notes) }}</textarea></div>
</div><div class="form-footer"><button class="btn">{{ $document->exists?'Save document details':'Upload securely' }}</button><a class="btn secondary" href="{{ route('document-library.index') }}">Cancel</a></div>
</form></div></div>
@endsection
