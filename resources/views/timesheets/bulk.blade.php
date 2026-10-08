@extends('layouts.app')
@section('title', 'Add hours by date range')
@section('eyebrow', $workforce === 'own' ? 'Own employees · Attendance' : 'Rental manpower · Timesheets')
@section('description', 'Fill 10 regular hours per day, skipping Fridays. Review every date before submitting.')
@section('actions')<a class="btn secondary" href="{{ route($routePrefix.'.index') }}">← Hours register</a>@endsection
@section('content')
<div class="card"><form class="filters" method="GET" action="{{ route($routePrefix.'.bulk') }}"><div class="field search"><label for="employee_search">Find employee</label><input id="employee_search" name="employee_search" value="{{ request('employee_search') }}" maxlength="100" placeholder="Name, Iqama or company"></div><button class="btn secondary">Search</button>@if(request('employee_search'))<a class="btn link" href="{{ route($routePrefix.'.bulk') }}">Clear</a>@endif<span class="help">Showing 50 active employees per page.</span></form>@include('partials.pagination', ['items'=>$employees])</div>
<div class="card"><div class="card-body">
<form method="POST" action="{{ route($routePrefix.'.bulk.store') }}" data-bulk-hours>
@csrf
<div class="form-grid">
<div class="field full"><label for="employee_id">Employee *</label><select id="employee_id" name="employee_id" required><option value="">Select employee</option>@foreach($employees as $employee)<option value="{{ $employee->id }}" {{ old('employee_id', request('employee_id')) == $employee->id ? 'selected' : '' }}>{{ $employee->name }} · {{ $employee->company->name }}</option>@endforeach</select></div>
<div class="field"><label for="range_start">Start date</label><input type="date" id="range_start" name="range_start" max="{{ now()->format('Y-m-d') }}" value="{{ old('range_start', now()->startOfMonth()->format('Y-m-d')) }}"></div>
<div class="field"><label for="range_end">End date</label><input type="date" id="range_end" name="range_end" max="{{ now()->format('Y-m-d') }}" value="{{ old('range_end', now()->format('Y-m-d')) }}"></div>
<div class="field full"><label for="total_hours">Total-hours target (optional)</label><input type="number" id="total_hours" name="total_hours" min="0.01" max="2880" step="0.01" value="{{ old('total_hours') }}"><p class="help">Leave empty to fill every eligible date. A target fills dates in order, with a shorter final day if needed. Generated hours are regular hours; adjust overtime separately.</p></div>
</div>
<div class="actions" style="margin:20px 0"><button type="button" class="btn secondary" data-generate-hours>Generate dates</button><button type="button" class="btn secondary" data-add-hours-date>+ Add date</button></div>
<p class="help">Generating again replaces this preview. You may manually add Fridays and enter 0 regular and 0 overtime for an absence. Up to 120 dates per submission. Dates must be on or after joining and no later than today.</p>
<p data-hours-message role="status" class="notice" hidden></p>
<div class="table-wrap"><table><thead><tr><th>Date</th><th>Regular hours</th><th>Overtime hours</th><th>Action</th></tr></thead><tbody data-hours-rows>
@foreach(old('entries', []) as $i => $entry)
<tr><td><input aria-label="Work date" type="date" name="entries[{{ $i }}][work_date]" max="{{ now()->format('Y-m-d') }}" value="{{ $entry['work_date'] ?? '' }}" required></td><td><input aria-label="Regular hours" type="number" name="entries[{{ $i }}][regular_hours]" min="0" max="24" step="0.01" value="{{ $entry['regular_hours'] ?? 10 }}" required></td><td><input aria-label="Overtime hours" type="number" name="entries[{{ $i }}][overtime_hours]" min="0" max="24" step="0.01" value="{{ $entry['overtime_hours'] ?? 0 }}" required></td><td><button type="button" class="btn danger small" data-remove-hours-date>Remove</button></td></tr>
@endforeach
</tbody></table></div>
<p data-hours-summary aria-live="polite" style="margin:20px 0"></p>
<div class="field"><label for="notes">Notes for all dates</label><textarea id="notes" name="notes" maxlength="1000">{{ old('notes') }}</textarea></div>
<p class="help">All dates are saved together for approval. If any date already exists or has a generated salary, nothing is saved. {{ $workforce === 'own' ? 'Own employees retain their separate fixed monthly salary.' : 'Approved regular and overtime hours feed rental payroll.' }}</p>
<div class="form-footer"><a class="btn secondary" href="{{ route($routePrefix.'.index') }}">Cancel</a><button class="btn" data-submit-hours {{ $employees->isEmpty() ? 'disabled' : '' }}>Submit dates →</button></div>
</form></div></div>
<script defer src="{{ asset('js/timesheet-range.js') }}?v={{ filemtime(public_path('js/timesheet-range.js')) }}"></script>
@endsection
