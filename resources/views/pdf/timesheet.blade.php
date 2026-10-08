<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>{{ $workforce === 'own' ? 'Attendance' : 'Timesheet' }} Summary - {{ $month }}</title>
<style>
@page{margin:18px 24px 38px}*{box-sizing:border-box}body{margin:0;font-family:'DejaVu Sans',sans-serif;color:#20383d;font-size:{{ $rows->count() > 80 ? '4.4px' : ($rows->count() > 50 ? '5.4px' : '7px') }};line-height:1}.hero{background:#10363b;color:#fff;padding:10px 16px;border-bottom:3px solid #19a896}.hero table,.summary,.register{width:100%;border-collapse:collapse}.hero td{border:0;padding:0;vertical-align:middle}.kicker{color:#8ee0cc;font-size:5px;font-weight:bold;letter-spacing:1.3px;text-transform:uppercase;margin-bottom:3px}.hero h1{font-size:16px;line-height:1;margin:0 0 3px}.period{text-align:right;color:#d6e8e4;font-size:7px}.summary{margin:5px 0 6px}.summary td{width:20%;background:#eef6f3;border-right:4px solid #fff;padding:4px 7px}.label{display:block;color:#68817f;font-size:4.8px;letter-spacing:.5px;text-transform:uppercase;margin-bottom:1px}.value{color:#153b40;font-size:8px;font-weight:bold}.section-title{font-size:6px;letter-spacing:1px;text-transform:uppercase;color:#173d42;border-bottom:1.5px solid #19a896;padding-bottom:2px;margin:5px 0 3px}.register{table-layout:fixed}.register thead{display:table-header-group}.register tr{page-break-inside:avoid}.register th{background:#173d42;color:#fff;text-align:left;font-size:4.7px;text-transform:uppercase;letter-spacing:.3px;padding:2px 4px}.register td{border-bottom:.5px solid #dce8e5;padding:{{ $rows->count() > 80 ? '.65px' : '2px' }} 4px;vertical-align:middle}.register tbody tr:nth-child(even) td{background:#f4f8f7}.register tr.short td{color:#a32020;background:#fff0f0}.num{text-align:right}.employee{font-weight:bold;color:#153b40}.muted{color:#708684}.status{white-space:nowrap}.approved{color:#16715d}.pending{color:#85620b}.rejected{color:#9a302b}.footer-grid{margin-top:6px;width:100%;border-collapse:separate;border-spacing:15px 0}.footer-grid td{width:33%;border-top:1px solid #809694;padding-top:3px;text-align:center;color:#607977}.empty{padding:25px;text-align:center;background:#f4f8f7}.notice{margin-top:4px;color:#687f7e;font-size:4.8px}
</style>
</head>
<body>
@include('pdf.company-brand')
<div class="hero"><table><tr><td><div class="kicker">{{ $documentBrand ? $documentBrand->company_name : 'Manpower Operations' }}</div><h1>{{ $workforce === 'own' ? 'Monthly Attendance Summary' : 'Monthly Timesheet Summary' }}</h1><div>One consolidated row per employee - repeated daily details removed</div></td><td class="period"><strong>{{ \Carbon\Carbon::createFromFormat('!Y-m', $month)->format('F Y') }}</strong><br>Generated {{ now()->format('d M Y, H:i') }} Riyadh time</td></tr></table></div>
<table class="summary"><tr>
<td><span class="label">Employees</span><span class="value">{{ number_format($rows->count()) }}</span></td>
<td><span class="label">Time entries</span><span class="value">{{ number_format($entries->count()) }}</span></td>
<td><span class="label">Regular hours</span><span class="value">{{ \App\Services\Pay::decimal($totals['regular']) }}</span></td>
<td><span class="label">Overtime hours</span><span class="value">{{ \App\Services\Pay::decimal($totals['overtime']) }}</span></td>
<td><span class="label">Applied filter</span><span class="value" style="font-size:7px">{{ $filters['status'] ? ucfirst($filters['status']) : 'All statuses' }}</span></td>
</tr></table>
<div class="section-title">Consolidated employee hours</div>
@if($rows->isEmpty())
<div class="empty"><strong>No time entries found</strong><br>The selected month and filters did not return any records.</div>
@else
<table class="register"><thead><tr><th style="width:3%">#</th><th style="width:15%">Employee</th><th style="width:11%">Designation</th><th style="width:14%">Company</th><th style="width:6%">Days</th><th style="width:12%">Date range</th><th class="num" style="width:8%">Regular h</th><th class="num" style="width:8%">Short h</th><th class="num" style="width:7%">OT h</th><th style="width:16%">Approval summary</th></tr></thead><tbody>
@foreach($rows as $index => $row)
<tr class="{{ $row['short_days'] > 0 ? 'short' : '' }}">
<td>{{ $index + 1 }}</td>
<td class="employee">{{ $row['employee']->name }}</td>
<td>{{ optional($row['employee']->designation)->name ?: 'Not specified' }}</td>
<td>{{ optional($row['employee']->company)->name ?: 'Not specified' }}</td>
<td>{{ $row['days'] }}</td>
<td>{{ $row['first_date']->format('d M') }}{{ $row['first_date']->format('Y-m-d') !== $row['last_date']->format('Y-m-d') ? ' - '.$row['last_date']->format('d M') : '' }}</td>
<td class="num"><strong>{{ \App\Services\Pay::decimal($row['regular']) }}</strong></td>
<td class="num"><strong>{{ \App\Services\Pay::decimal(max(0,$row['shortfall'])) }}</strong><br>{{ $row['short_days'] }} date(s)</td>
<td class="num"><strong>{{ \App\Services\Pay::decimal($row['overtime']) }}</strong></td>
<td class="status"><span class="approved">A {{ $row['approved'] }}</span> &nbsp; <span class="pending">P {{ $row['pending'] }}</span> &nbsp; <span class="rejected">R {{ $row['rejected'] }}</span></td>
</tr>
@endforeach
</tbody></table>
@endif
<table class="footer-grid"><tr><td>Prepared by</td><td>Reviewed by</td><td>Authorized signature</td></tr></table>
<div class="notice">A = approved, P = pending, R = rejected. The report consolidates all matching daily entries into one employee row. Detailed notes remain available in the application.</div>
</body>
</html>
