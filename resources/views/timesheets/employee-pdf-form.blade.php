@extends('layouts.app')
@section('title', 'Generate employee timesheet')
@section('eyebrow', 'Timesheet · Print document')
@section('description', 'Create a black-and-white employee timesheet for one or several months.')
@section('content')
<div class="columns"><div class="card"><div class="card-head"><h2>{{ $employee->name }}</h2><span class="badge">{{ $employee->company->name }}</span></div><div class="card-body">
<form method="POST" action="{{ route($routePrefix.'.employee.pdf', $employee) }}">@csrf
<div class="field"><label>Select month(s) *</label>
@forelse($months as $month)<label class="company-row" style="cursor:pointer"><span><input type="checkbox" name="months[]" value="{{ $month }}" {{ in_array($month, old('months', [$months->first()])) ? 'checked' : '' }}> {{ \Carbon\Carbon::createFromFormat('!Y-m', $month)->format('F Y') }}</span><span class="badge">Available</span></label>
@empty<div class="empty"><strong>No timesheet months available.</strong><p>Add time entries before generating this document.</p></div>@endforelse
</div>
<div class="field" style="margin-top:20px"><label>Page orientation *</label><div class="actions"><label><input type="radio" name="orientation" value="portrait" {{ old('orientation', 'portrait') === 'portrait' ? 'checked' : '' }}> Portrait</label><label><input type="radio" name="orientation" value="landscape" {{ old('orientation') === 'landscape' ? 'checked' : '' }}> Landscape</label></div><p class="help">Each selected month is arranged on one A4 page.</p></div>
<div class="form-footer"><a class="btn secondary" href="{{ route($routePrefix.'.index') }}">Cancel</a><button class="btn" {{ $months->isEmpty() ? 'disabled' : '' }}>Generate black & white PDF →</button></div>
</form></div></div>
<div class="card"><div class="card-body"><div class="eyebrow">Document format</div><h3>Employee timesheet, ready for signature.</h3><p class="description">This is a formal black-and-white timesheet, not the landscape management report. It includes employee details, daily hours, approval status, monthly totals, and signature lines.</p><p class="description" style="margin-top:14px">Selecting multiple months creates one page per month in a single PDF.</p></div></div></div>
@endsection
