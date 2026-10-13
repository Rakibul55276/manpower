@extends('layouts.app')
@section('title', 'Audit & custom reports')
@section('eyebrow', 'Company audit center')
@section('description', 'Build one company-scoped report across enabled modules and system events.')
@section('actions')
<a class="btn secondary" href="{{ route('audit.csv', $filters) }}">↓ Export CSV</a>
<a class="btn" href="{{ route('audit.pdf', $filters) }}">↓ Export PDF</a>
@endsection
@section('content')
<div class="grid-2" style="margin-bottom:22px">
    <a class="stat accent stat-link" href="#customize-report"><div class="label">Selected report</div><div class="value" style="font-size:22px">{{ $reportTitle }}</div><div class="note">{{ $filters['from'] }} through {{ $filters['to'] }} · Customize →</div></a>
    <a class="stat stat-link" href="#report-results"><div class="label">Matching records</div><div class="value">{{ number_format($total) }}</div><div class="note">@if(config('manpower.enabled'))Filtered across {{ empty($filters['employment_type']) ? 'both employee types' : $filters['employment_type'].' employees' }} · @endif View results →</div></a>
</div>
<div class="card" id="customize-report">
    <div class="card-head"><h2>Customize report</h2><span class="badge">Enabled modules</span></div>
    <form method="GET" action="{{ route('audit.index') }}">
        <div class="filters">
            <div class="field"><label for="report">Report type</label><select id="report" name="report">
                <option value="activity" {{ $filters['report'] === 'activity' ? 'selected' : '' }}>Activity trail</option>
                @if(config('manpower.enabled'))
                <option value="employees" {{ $filters['report'] === 'employees' ? 'selected' : '' }}>Employee details</option>
                <option value="timesheets" {{ $filters['report'] === 'timesheets' ? 'selected' : '' }}>Timesheet & attendance</option>
                <option value="salaries" {{ $filters['report'] === 'salaries' ? 'selected' : '' }}>Salary details</option>
                @endif
                @if(config('safety_shop.enabled') && auth()->user()->isCompanyAdmin())
                <option value="shop_sales" {{ $filters['report'] === 'shop_sales' ? 'selected' : '' }}>Safety Shop sales</option>
                <option value="shop_returns" {{ $filters['report'] === 'shop_returns' ? 'selected' : '' }}>Safety Shop returns</option>
                <option value="stock_movements" {{ $filters['report'] === 'stock_movements' ? 'selected' : '' }}>Safety Shop stock movements</option>
                @endif
            </select></div>
            <div class="field"><label for="from">From</label><input id="from" type="date" name="from" value="{{ $filters['from'] }}" required></div>
            <div class="field"><label for="to">To</label><input id="to" type="date" name="to" value="{{ $filters['to'] }}" required></div>
            @if(config('manpower.enabled'))<div class="field"><label for="employment_type">Employee type</label><select id="employment_type" name="employment_type"><option value="">Both types</option><option value="rental" {{ ($filters['employment_type'] ?? '') === 'rental' ? 'selected' : '' }}>Rental employee</option><option value="own" {{ ($filters['employment_type'] ?? '') === 'own' ? 'selected' : '' }}>Own employee</option></select></div>@endif
            <div class="field"><label for="company_id">Company</label><select id="company_id" name="company_id"><option value="">All companies</option>@foreach($companies as $company)<option value="{{ $company->id }}" {{ ($filters['company_id'] ?? '') == $company->id ? 'selected' : '' }}>{{ $company->name }}</option>@endforeach</select></div>
            @if($locations->isNotEmpty())<div class="field"><label for="location_id">Shop location</label><select id="location_id" name="location_id"><option value="">All shop locations</option>@foreach($locations as $location)<option value="{{ $location->id }}" {{ ($filters['location_id'] ?? '') == $location->id ? 'selected' : '' }}>{{ $location->name }}</option>@endforeach</select></div>@endif
            @if($employees->isNotEmpty())<div class="field"><label for="employee_id">Selected employee</label><select id="employee_id" name="employee_id"><option value="">All employees</option>@foreach($employees as $employee)<option value="{{ $employee->id }}" selected>{{ $employee->name }} · {{ ucfirst($employee->employment_type) }}</option>@endforeach</select></div>@endif
            <div class="field"><label for="status">Status</label><select id="status" name="status"><option value="">All statuses</option>@foreach(['active','inactive','pending','approved','rejected','paid','void'] as $status)<option value="{{ $status }}" {{ ($filters['status'] ?? '') === $status ? 'selected' : '' }}>{{ ucfirst($status) }}</option>@endforeach</select></div>
            <div class="field"><label for="user_id">Actor (activity)</label><select id="user_id" name="user_id"><option value="">All users</option>@foreach($users as $user)<option value="{{ $user->id }}" {{ ($filters['user_id'] ?? '') == $user->id ? 'selected' : '' }}>{{ $user->name }} ({{ $user->username }})</option>@endforeach</select></div>
            <div class="field"><label for="action">Event type</label><select id="action" name="action"><option value="">All events</option>@foreach($actions as $action)<option value="{{ $action }}" {{ ($filters['action'] ?? '') === $action ? 'selected' : '' }}>{{ ucfirst(str_replace('_',' ',$action)) }}</option>@endforeach</select></div>
            <div class="field search"><label for="search">Search</label><input id="search" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Employee, Iqama, company, event…"></div>
        </div>
        <div class="form-footer" style="padding:0 23px 20px"><a class="btn secondary" href="{{ route('audit.index') }}">Clear filters</a><button class="btn">Generate report</button></div>
    </form>
</div>
<div class="card" id="report-results">
    <div class="card-head"><h2>{{ $reportTitle }}</h2><span class="badge">{{ number_format($total) }} records</span></div>
    <div class="stats" style="padding:18px 23px;grid-template-columns:repeat({{ min(count($summary), 4) }},minmax(0,1fr))">@foreach($summary as $label => $value)<div class="stat"><div class="label">{{ $label }}</div><div class="value" style="font-size:24px">{{ $value }}</div></div>@endforeach</div>
    <div class="table-wrap"><table><thead><tr>@foreach($headings as $heading)<th>{{ $heading }}</th>@endforeach</tr></thead><tbody>
    @forelse($rows as $row)<tr>@foreach($row as $value)<td class="wrap">{{ $value === null || $value === '' ? '—' : $value }}</td>@endforeach</tr>
    @empty<tr><td colspan="{{ count($headings) }}"><div class="empty"><strong>No records found</strong><p>Adjust the report type, period, or filters.</p></div></td></tr>@endforelse
    </tbody>@if($filters['report'] === 'salaries' && $total)<tfoot><tr><th colspan="4">TOTAL</th><th>{{ $summary['Regular pay · SAR'] }}</th><th>{{ $summary['Overtime pay · SAR'] }}</th><th>{{ $summary['Allowances · SAR'] }}</th><th>{{ $summary['Deductions · SAR'] }}</th><th>{{ $summary['Net pay · SAR'] }}</th><th>—</th></tr></tfoot>@elseif($filters['report'] === 'timesheets' && $total)<tfoot><tr><th colspan="4">TOTAL</th><th>{{ $summary['Regular hours'] }}</th><th>{{ $summary['Overtime hours'] }}</th><th colspan="2">{{ $summary['Records'] }} records</th></tr></tfoot>@endif</table></div>
    @include('partials.pagination', ['items' => $records])
</div>
@endsection
