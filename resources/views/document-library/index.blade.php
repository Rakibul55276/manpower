@extends('layouts.app')
@section('title','Document library')
@section('eyebrow','Private scanned records')
@section('description','Securely store, find, preview, and download scanned documents.')
@section('actions')<a class="btn" href="{{ route('document-library.create') }}">+ Upload document</a>@endsection
@section('content')
<link rel="stylesheet" href="{{ asset('css/document-library.css') }}">
<div class="library-overview">
<div><span class="library-overview-icon">▧</span><div><strong>{{ number_format($documents->total()) }}</strong><small>{{ Str::plural('secure document',$documents->total()) }}</small></div></div>
<div><span class="library-overview-icon">⌾</span><div><strong>{{ auth()->user()->isSuperAdmin()?'All users':'Private' }}</strong><small>{{ auth()->user()->isSuperAdmin()?'System-wide document access':(auth()->user()->isAdmin()?'Your documents · deletion permitted':'Your documents · deletion restricted') }}</small></div></div>
</div>
<div class="card library-filter-card"><form class="filters" method="GET">
<div class="field search"><label for="search">Search documents</label><input id="search" name="search" value="{{ request('search') }}" placeholder="Title, document number, or filename"></div>
<div class="field"><label for="category">Category</label><select id="category" name="category"><option value="">All categories</option>@foreach($categories as $category)<option value="{{ $category }}" {{ request('category')===$category?'selected':'' }}>{{ $category }}</option>@endforeach</select></div>
@if(auth()->user()->isSuperAdmin())<div class="field"><label for="owner_id">Owner</label><select id="owner_id" name="owner_id"><option value="">All users</option>@foreach($users as $user)<option value="{{ $user->id }}" {{ request('owner_id')==$user->id?'selected':'' }}>{{ $user->name }} · {{ $user->username }}</option>@endforeach</select></div>@endif
<button class="btn secondary">Apply filters</button><a class="btn link" href="{{ route('document-library.index') }}">Clear filters</a>
</form></div>
@if($documents->isEmpty())
<div class="card"><div class="empty library-empty"><span>▧</span><strong>No scanned documents found</strong><p>{{ request()->hasAny(['search','category','owner_id'])?'No documents match the selected filters.':'Upload a PDF or image to start your private document library.' }}</p><a class="btn" href="{{ route('document-library.create') }}">Upload document</a></div></div>
@else
<div class="document-grid">
@foreach($documents as $document)
@php $isPdf=str_contains(strtolower($document->mime_type),'pdf'); $expired=$document->expires_on&&$document->expires_on->isPast(); $expiring=$document->expires_on&&!$expired&&$document->expires_on->lte(now()->addDays(30)); @endphp
<article class="document-card">
<a class="document-preview {{ $isPdf?'pdf':'image' }}" href="{{ route('document-library.show',$document) }}"><span class="document-type-icon">{{ $isPdf?'PDF':'IMG' }}</span><span class="document-extension">{{ strtoupper(pathinfo($document->original_name,PATHINFO_EXTENSION)) }}</span>@if($expired)<span class="document-state expired">Expired</span>@elseif($expiring)<span class="document-state expiring">Expiring soon</span>@endif</a>
<div class="document-card-body">
<div class="document-category">{{ $document->category ?: 'Uncategorized' }}</div>
<a class="document-title" href="{{ route('document-library.show',$document) }}">{{ $document->title }}</a>
<div class="document-number">{{ $document->document_number ?: 'No document number' }}</div>
@if(auth()->user()->isSuperAdmin())<div class="document-owner"><span>{{ strtoupper(substr($document->owner->name,0,1)) }}</span><div><strong>{{ $document->owner->name }}</strong><small>{{ $document->owner->username }}</small></div></div>@endif
<div class="document-meta"><span>Uploaded {{ $document->created_at->format('d M Y') }}</span><span>{{ number_format($document->size_bytes/1024,1) }} KB</span></div>
@if($document->expires_on)<div class="document-expiry {{ $expired?'expired':($expiring?'expiring':'') }}">Expires {{ $document->expires_on->format('d M Y') }}</div>@endif
</div>
<div class="document-actions"><a target="_blank" href="{{ route('document-library.view',$document) }}">Preview</a><a href="{{ route('document-library.download',$document) }}">Download</a><a href="{{ route('document-library.edit',$document) }}">Edit</a></div>
</article>
@endforeach
</div>
<div class="card library-pagination">@include('partials.pagination',['items'=>$documents])</div>
@endif
@endsection
