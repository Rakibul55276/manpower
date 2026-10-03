@extends('layouts.app')
@section('title', 'Activity log')
@section('eyebrow', 'Super Admin · Administration')
@section('description', 'A history of account access and changes to workforce records.')
@section('content')<div class="card"><div class="table-wrap"><table><thead><tr><th>When · Riyadh</th><th>User</th><th>Action</th><th>Record</th></tr></thead><tbody>@forelse($logs as $log)<tr><td>{{ $log->created_at->format('d M Y · H:i') }}</td><td>{{ optional($log->user)->name ?? 'System' }}</td><td>{{ $log->action }}</td><td class="wrap">{{ $log->subject }}</td></tr>@empty<tr><td colspan="4"><div class="empty">Activity will appear here as your team works.</div></td></tr>@endforelse</tbody></table></div>@include('partials.pagination', ['items' => $logs])</div>@endsection
