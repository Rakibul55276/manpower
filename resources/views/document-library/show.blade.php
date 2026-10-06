@extends('layouts.app')
@section('title',$document->title)
@section('eyebrow','Document library · Secure record')
@section('description',$document->original_name)
@section('actions')<a class="btn secondary" target="_blank" href="{{ route('document-library.view',$document) }}">View scan</a><a class="btn secondary" href="{{ route('document-library.download',$document) }}">Download</a><a class="btn" href="{{ route('document-library.edit',$document) }}">Edit details</a>@endsection
@section('content')
<div class="card"><div class="card-head"><h2>Document details</h2><span class="badge approved">Private</span></div><div class="card-body"><dl class="detail-list">
<div><dt>Owner</dt><dd>{{ $document->owner->name }} · {{ $document->owner->username }}</dd></div>
<div><dt>Category</dt><dd>{{ $document->category ?: 'Uncategorized' }}</dd></div>
<div><dt>Document number</dt><dd>{{ $document->document_number ?: '—' }}</dd></div>
<div><dt>Issue date</dt><dd>{{ $document->issued_on?$document->issued_on->format('d M Y'):'—' }}</dd></div>
<div><dt>Expiry date</dt><dd>{{ $document->expires_on?$document->expires_on->format('d M Y'):'—' }}</dd></div>
<div><dt>File</dt><dd>{{ $document->original_name }} · {{ number_format($document->size_bytes/1024,1) }} KB</dd></div>
<div><dt>Uploaded by</dt><dd>{{ $document->uploader->name }} on {{ $document->created_at->format('d M Y H:i') }}</dd></div>
<div><dt>Notes</dt><dd>{{ $document->notes ?: '—' }}</dd></div>
</dl></div></div>
@if(auth()->user()->isAdmin())<div class="card"><div class="card-body"><h3>Administrative action</h3><p class="description">Deleting removes the database record and private stored file. This action is logged. Managers cannot delete documents.</p><form method="POST" action="{{ route('document-library.destroy',$document) }}" onsubmit="return confirm('Delete this document permanently?')">@csrf @method('DELETE')<button class="btn danger">Delete document</button></form></div></div>@endif
@endsection
