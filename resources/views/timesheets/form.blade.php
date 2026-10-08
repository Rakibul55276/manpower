@extends('layouts.app')
@section('title', ($timesheet->exists ? 'Edit ' : 'Add ').($workforce === 'own' ? 'attendance & overtime' : 'timesheet hours'))
@section('eyebrow', $workforce === 'own' ? 'Own employees · Attendance' : 'Rental manpower · Timesheets')
@section('description', 'One entry per employee per day. New entries remain pending for Admin approval.')
@section('actions')<a class="btn secondary" href="{{ route($routePrefix.'.index') }}">← Hours register</a>@endsection
@section('content')
@if(!$timesheet->exists)<div class="card"><form class="filters" method="GET" action="{{ route($routePrefix.'.create') }}"><div class="field search"><label for="employee_search">Find employee</label><input id="employee_search" name="employee_search" value="{{ request('employee_search') }}" maxlength="100" placeholder="Name, Iqama or company"></div><button class="btn secondary">Search</button>@if(request('employee_search'))<a class="btn link" href="{{ route($routePrefix.'.create') }}">Clear</a>@endif<span class="help">Showing up to 50 matching active employees.</span></form></div>@endif
<div class="columns"><div class="card"><div class="card-head"><h2>Daily {{ $workforce === 'own' ? 'attendance' : 'hours' }}</h2></div><div class="card-body">
<form method="POST" action="{{ $timesheet->exists ? route($routePrefix.'.update', $timesheet) : route($routePrefix.'.store') }}">@csrf @if($timesheet->exists) @method('PUT') @endif
<div class="form-grid">
<div class="field full"><label for="employee_id">Employee *</label><select id="employee_id" name="employee_id" data-employee-hours {{ $timesheet->exists ? 'data-editing=1' : '' }} required><option value="">Select employee</option>@foreach($employees as $employee)<option value="{{ $employee->id }}" data-regular-hours="{{ \App\Services\Pay::decimal($employee->regular_hours_units ?: 800) }}" {{ old('employee_id', $timesheet->employee_id ?? request('employee_id')) == $employee->id ? 'selected' : '' }}>{{ $employee->name }} · {{ $employee->company->name }}</option>@endforeach</select></div>
<div class="field full"><label for="work_date">Work date *</label><input id="work_date" name="work_date" type="date" value="{{ old('work_date', optional($timesheet->work_date)->format('Y-m-d')) }}" max="{{ now()->format('Y-m-d') }}" required></div>
@if($workforce === 'own')
<div class="field"><label><input type="checkbox" name="attended" value="1" {{ old('attended', $timesheet->exists ? $timesheet->regular_units > 0 : true) ? 'checked' : '' }}> Present / attended</label><p class="help">A checkmark records one attendance day. Fixed monthly salary is unchanged.</p></div>
<input type="hidden" name="attendance_mode" value="1">
<input type="hidden" name="regular_hours" value="8">
@else
<div class="field"><label for="regular_hours">Hours worked *</label><input id="regular_hours" name="regular_hours" type="number" min="0" max="24" step="0.01" required value="{{ old('regular_hours', \App\Services\Pay::decimal($timesheet->regular_units)) }}"><p class="help">Enter 0 for an absence. Hours above the employee's regular daily target are moved automatically to overtime.</p></div>
@endif
<div class="field"><label for="overtime_hours">Overtime hours *</label><input id="overtime_hours" name="overtime_hours" type="number" min="0" max="24" step="0.01" required value="{{ old('overtime_hours', \App\Services\Pay::decimal($timesheet->overtime_units)) }}"></div>
<div class="field full"><label for="notes">Work notes</label><textarea id="notes" name="notes" maxlength="1000">{{ old('notes', $timesheet->notes) }}</textarea></div>
</div><div class="form-footer"><a href="{{ route($routePrefix.'.index') }}" class="btn secondary">Cancel</a><button class="btn" {{ $employees->isEmpty() ? 'disabled' : '' }}>Submit for approval →</button></div></form>
</div></div><div><div class="card"><div class="card-body"><div class="eyebrow">Approval workflow</div><h3>{{ $workforce === 'own' ? 'Check attendance and add overtime only.' : 'Every approved hour counts.' }}</h3><p class="description">Enter hours only. Payroll automatically uses the employee's configured salary settings after approval.</p><p class="description" style="margin-top:15px">Admin approves or rejects pending entries. Only Super Admin can reopen an approved entry.</p></div></div></div></div>
@endsection
