@extends('layouts.app')
@section('title', 'Audit & custom reports')
@section('eyebrow', 'Super Admin · Reporting')
@section('description', 'Build employee, timesheet, attendance, salary, and transaction reports for rental and own employees.')
@section('actions')
<a class="btn secondary" href="{{ route('audit.csv', $filters) }}">↓ Export CSV</a>
<a class="btn" href="{{ route('audit.pdf', $filters) }}">↓ Export PDF</a>
@endsection
@section('content')
<div class="grid-2" style="margin-bottom:22px">
    <a class="stat accent stat-link" href="#customize-report"><div class="label">Selected report</div><div class="value" style="font-size:22px">{{ $reportTitle }}</div><div class="note">{{ $filters['from'] }} through {{ $filters['to'] }} · Customize →</div></a>
    <a class="stat stat-link" href="#report-results"><div class="label">Matching records</div><div class="value">{{ number_format($total) }}</div><div class="note">Filtered across {{ empty($filters['employment_type']) ? 'both employee types' : $filters['employment_type'].' employees' }} · View results →</div></a>
</div>
<div class="card" id="customize-report">
    <div class="card-head"><h2>Customize report</h2><span class="badge">Rental & own employees</span></div>
    <form method="GET" action="{{ route('audit.index') }}">
        <div class="filters">
            <div class="field"><label for="report">Report type</label><select id="report" name="report">
                <option value="activity" {{ $filters['report'] === 'activity' ? 'selected' : '' }}>Activity trail</option>
                <option value="employees" {{ $filters['report'] === 'employees' ? 'selected' : '' }}>Employee details</option>
                <option value="timesheets" {{ $filters['report'] === 'timesheets' ? 'selected' : '' }}>Timesheet & attendance</option>
                <option value="salaries" {{ $filters['report'] === 'salaries' ? 'selected' : '' }}>Salary details</option>
            </select></div>
            <div class="field"><label for="from">From</label><input id="from" type="date" name="from" value="{{ $filters['from'] }}" required></div>
            <div class="field"><label for="to">To</label><input id="to" type="date" name="to" value="{{ $filters['to'] }}" required></div>
            <div class="field"><label for="employment_type">Employee type</label><select id="employment_type" name="employment_type"><option value="">Both types</option><option value="rental" {{ ($filters['employment_type'] ?? '') === 'rental' ? 'selected' : '' }}>Rental employee</option><option value="own" {{ ($filters['employment_type'] ?? '') === 'own' ? 'selected' : '' }}>Own employee</option></select></div>
            <div class="field"><label for="company_id">Company</label><select id="company_id" name="company_id"><option value="">All companies</option>@foreach($companies as $company)<option value="{{ $company->id }}" {{ ($filters['company_id'] ?? '') == $company->id ? 'selected' : '' }}>{{ $company->name }}</option>@endforeach</select></div>
            <div class="field"><label for="employee_id">Employee</label><select id="employee_id" name="employee_id"><option value="">All employees</option>@foreach($employees as $employee)<option value="{{ $employee->id }}" {{ ($filters['employee_id'] ?? '') == $employee->id ? 'selected' : '' }}>{{ $employee->name }} · {{ ucfirst($employee->employment_type) }}</option>@endforeach</select></div>
            <div class="field"><label for="status">Status</label><select id="status" name="status"><option value="">All statuses</option>@foreach(['active','inactive','pending','approved','rejected','paid','void'] as $status)<option value="{{ $status }}" {{ ($filters['status'] ?? '') === $status ? 'selected' : '' }}>{{ ucfirst($status) }}</option>@endforeach</select></div>
            <div class="field"><label for="user_id">Actor (activity)</label><select id="user_id" name="user_id"><option value="">All users</option>@foreach($users as $user)<option value="{{ $user->id }}" {{ ($filters['user_id'] ?? '') == $user->id ? 'selected' : '' }}>{{ $user->name }} ({{ $user->username }})</option>@endforeach</select></div>
            <div class="field"><label for="action">Event (activity)</label><select id="action" name="action"><option value="">All events</option>@foreach($actions as $action)<option value="{{ $action }}" {{ ($filters['action'] ?? '') === $action ? 'selected' : '' }}>{{ $action }}</option>@endforeach</select></div>
            <div class="field search"><label for="search">Search</label><input id="search" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Employee, Iqama, company, event…"></div>
        </div>
        <div class="form-footer" style="padding:0 23px 20px"><a class="btn secondary" href="{{ route('audit.index') }}">Clear filters</a><button class="btn">Generate report</button></div>
    </form>
</div>
<div class="card" id="report-results">
    <div class="card-head"><h2>{{ $reportTitle }}</h2><span class="badge">{{ number_format($total) }} records</span></div>
    <div class="table-wrap"><table><thead><tr>@foreach($headings as $heading)<th>{{ $heading }}</th>@endforeach</tr></thead><tbody>
    @forelse($rows as $row)<tr>@foreach($row as $value)<td class="wrap">{{ $value === null || $value === '' ? '—' : $value }}</td>@endforeach</tr>
    @empty<tr><td colspan="{{ count($headings) }}"><div class="empty"><strong>No records found</strong><p>Adjust the report type, period, or filters.</p></div></td></tr>@endforelse
    </tbody></table></div>
    @include('partials.pagination', ['items' => $records])
</div>
@endsection
