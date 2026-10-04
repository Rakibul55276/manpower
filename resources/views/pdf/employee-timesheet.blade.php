<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Employee Timesheet - {{ $employee->name }}</title>
<style>
@page{margin:24px 28px 42px}*{box-sizing:border-box}body{margin:0;font-family:'DejaVu Sans',sans-serif;color:#111;font-size:{{ $orientation === 'landscape' ? '7px' : '8px' }};line-height:1.25}.sheet{width:100%}.heading{width:100%;border-collapse:collapse;border:2px solid #111}.heading td{padding:9px 11px;vertical-align:middle}.heading .title{font-size:20px;font-weight:bold;letter-spacing:.4px}.heading .month{text-align:right;font-size:14px;font-weight:bold}.details{width:100%;border-collapse:collapse;margin-top:8px}.details td{width:25%;border:1px solid #222;padding:6px 8px}.label{display:block;font-size:6px;text-transform:uppercase;letter-spacing:.7px;color:#555;margin-bottom:2px}.value{font-size:9px;font-weight:bold}.hours{width:100%;border-collapse:collapse;margin-top:9px;table-layout:fixed}.hours thead{display:table-header-group}.hours tr{page-break-inside:avoid}.hours th,.hours td{border:1px solid #222;padding:{{ $orientation === 'landscape' ? '2px 5px' : '3px 5px' }};vertical-align:middle}.hours th{background:#eee;text-align:left;font-size:6.5px;text-transform:uppercase;letter-spacing:.35px}.num{text-align:right}.center{text-align:center}.totals{width:100%;border-collapse:collapse;margin-top:7px}.totals td{border:1px solid #111;padding:6px 8px}.totals .amount{font-size:10px;font-weight:bold;text-align:right}.certification{margin-top:9px;border:1px solid #222;padding:7px;line-height:1.4}.signatures{width:100%;border-collapse:separate;border-spacing:18px 0;margin-top:{{ $orientation === 'landscape' ? '16px' : '25px' }}.signatures td{width:33%;border-top:1px solid #111;text-align:center;padding-top:5px}.note{margin-top:6px;font-size:6px;color:#444}.status{font-weight:bold;text-transform:uppercase}.pending{color:#555}.approved{color:#000}.rejected{text-decoration:line-through}
</style>
</head>
<body>
@foreach($sheets as $sheet)
<div class="sheet">
<table class="heading"><tr><td><div class="label">Manpower workforce management</div><div class="title">EMPLOYEE TIMESHEET</div></td><td class="month">{{ \Carbon\Carbon::createFromFormat('!Y-m', $sheet['month'])->format('F Y') }}</td></tr></table>
<table class="details"><tr><td><span class="label">Employee name</span><span class="value">{{ $employee->name }}</span></td><td><span class="label">Employee ID</span><span class="value">EMP-{{ str_pad($employee->id, 4, '0', STR_PAD_LEFT) }}</span></td><td><span class="label">Company</span><span class="value">{{ $employee->company->name }}</span></td><td><span class="label">Location</span><span class="value">{{ $employee->company->location }}</span></td></tr><tr><td><span class="label">Designation</span><span class="value">{{ $employee->designation->name }}</span></td><td><span class="label">Iqama number</span><span class="value">{{ $employee->iqama_number }}</span></td><td><span class="label">Document</span><span class="value">Monthly hours record</span></td><td><span class="label">Employment type</span><span class="value">{{ ucfirst($employee->employment_type) }}</span></td></tr></table>
<table class="hours"><thead><tr><th style="width:7%">No.</th><th style="width:16%">Date</th><th style="width:10%">Day</th><th class="num" style="width:13%">Regular h</th><th class="num" style="width:13%">Overtime h</th><th class="num" style="width:13%">Total h</th><th style="width:13%">Status</th><th style="width:15%">Initial</th></tr></thead><tbody>
@foreach($sheet['entries'] as $index => $entry)<tr><td class="center">{{ $index + 1 }}</td><td>{{ $entry->work_date->format('d M Y') }}</td><td>{{ $entry->work_date->format('D') }}</td><td class="num">{{ \App\Services\Pay::decimal($entry->regular_units) }}</td><td class="num">{{ \App\Services\Pay::decimal($entry->overtime_units) }}</td><td class="num"><strong>{{ \App\Services\Pay::decimal($entry->regular_units + $entry->overtime_units) }}</strong></td><td><span class="status {{ $entry->status }}">{{ $entry->status }}</span></td><td></td></tr>@endforeach
</tbody></table>
<table class="totals"><tr><td>Monthly totals</td><td class="amount">Regular: {{ \App\Services\Pay::decimal($sheet['regular']) }} h</td><td class="amount">Overtime: {{ \App\Services\Pay::decimal($sheet['overtime']) }} h</td><td class="amount">Combined: {{ \App\Services\Pay::decimal($sheet['regular'] + $sheet['overtime']) }} h</td></tr></table>
<div class="certification">I certify that the hours shown above are complete and accurate for the stated employee and month. Any correction must be recorded in the Manpower system before payroll is generated.</div>
<table class="signatures"><tr><td>Employee signature / date</td><td>Supervisor signature / date</td><td>Authorized approval / date</td></tr></table>
<div class="note">Generated {{ now()->format('d M Y, H:i') }} Riyadh time. Black-and-white official timesheet. System approval status is shown for each entry.</div>
</div>
@if(!$loop->last)<div style="page-break-after:always"></div>@endif
@endforeach
</body>
</html>
