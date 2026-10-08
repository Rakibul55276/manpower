<?php
namespace App\Http\Controllers;
use App\Models\Timesheet;
use App\Models\Employee;
use App\Models\Payroll;
use App\Models\ActivityLog;
use App\Services\Access;
use App\Services\Pay;
use App\Services\Documents;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
class TimesheetController extends Controller
{
    private function workforce() { return request()->route('workforce') ?? 'rental'; }
    private function prefix() { return $this->workforce() === 'own' ? 'attendance' : 'timesheets'; }
    private function check(Timesheet $timesheet) { Access::company($timesheet->company_id ?: $timesheet->employee->company_id); Access::branch($timesheet->branch_id ?: $timesheet->employee->branch_id, $timesheet->company_id ?: $timesheet->employee->company_id); abort_unless($timesheet->employee->employment_type === $this->workforce(), 404); }
    public function index(Request $request)
    {
        $request->validate(['month' => 'nullable|date_format:Y-m', 'status' => 'nullable|in:pending,approved,rejected', 'employee_id' => 'nullable|integer', 'company_id' => 'nullable|integer', 'employee_search' => 'nullable|string|max:100']);
        $month = $request->month ?? now()->format('Y-m'); $workforce = $this->workforce(); $routePrefix = $this->prefix();
        $query = $this->filteredQuery($request, $month, $workforce);
        $totals = ['regular' => (clone $query)->sum('regular_units'), 'overtime' => (clone $query)->sum('overtime_units')];
        // A monthly employee view contains at most 31 dates, so 50 rows keeps the
        // complete month together while still bounding broader unfiltered lists.
        $timesheets = $query->orderByDesc('work_date')->paginate(50)->withQueryString();
        $companies = Access::companies()->orderBy('name')->get();
        $directoryQuery = Access::employees()->where('employment_type', $workforce)->with(['company', 'designation'])->orderBy('name');
        if ($request->filled('company_id')) { $directoryQuery->where('company_id', (int) $request->company_id); }
        if ($request->filled('employee_search')) {
            $search = trim($request->employee_search);
            $directoryQuery->where(function ($q) use ($search) { $q->where('name', 'like', '%'.$search.'%')->orWhere('iqama_number', 'like', '%'.$search.'%')->orWhereHas('company', function ($company) use ($search) { $company->where('name', 'like', '%'.$search.'%'); })->orWhereHas('designation', function ($designation) use ($search) { $designation->where('name', 'like', '%'.$search.'%'); }); });
        }
        $directoryEmployees = $directoryQuery->paginate(25, ['*'], 'directory_page')->withQueryString();
        $directoryEntries = $this->filteredQuery(new Request($request->only(['status', 'company_id'])), $month, $workforce)->whereIn('employee_id', $directoryEmployees->pluck('id'))->get()->groupBy('employee_id');
        $employeeDirectory = $directoryEmployees->getCollection()->map(function ($employee) use ($directoryEntries) {
            $entries = $directoryEntries->get($employee->id, collect());
            return ['employee' => $employee, 'entries' => $entries->count(), 'regular' => $entries->sum('regular_units'), 'overtime' => $entries->sum('overtime_units'), 'pending' => $entries->where('status', 'pending')->count()];
        })->values();
        $selectedEmployee = $request->filled('employee_id') ? Access::employees()->where('employment_type', $workforce)->find($request->employee_id) : null;
        return view('timesheets.index', compact('timesheets', 'companies', 'month', 'workforce', 'routePrefix', 'totals', 'employeeDirectory', 'directoryEmployees', 'selectedEmployee'));
    }
    private function filteredQuery(Request $request, $month, $workforce)
    {
        $query = Timesheet::with(['employee.company', 'employee.designation', 'creator', 'reviewer'])
            ->whereIn('employee_id', Access::employees()->where('employment_type', $workforce)->select('id'))
            ->where('work_date', 'like', $month.'%');
        $user=auth()->user(); if(!$user->isSuperAdmin())$query->where('company_id',$user->company_id); if($user->isManager())$query->where('branch_id',$user->branch_id);
        if ($request->filled('status')) { $query->where('status', $request->status); }
        if ($request->filled('employee_id')) { $query->where('employee_id', $request->employee_id); }
        if ($request->filled('employee_search')) { $search = trim($request->employee_search); $query->whereHas('employee', function ($employee) use ($search) { $employee->where('name', 'like', '%'.$search.'%')->orWhere('iqama_number', 'like', '%'.$search.'%'); }); }
        if ($request->filled('company_id')) { $query->whereHas('employee', function ($q) use ($request) { $q->where('company_id', $request->company_id); }); }
        return $query;
    }
    public function pdf(Request $request)
    {
        $request->validate(['month' => 'nullable|date_format:Y-m', 'status' => 'nullable|in:pending,approved,rejected', 'employee_id' => 'nullable|integer', 'company_id' => 'nullable|integer']);
        $month = $request->month ?? now()->format('Y-m');
        $workforce = $this->workforce();
        $entries = $this->filteredQuery($request, $month, $workforce)->orderBy('work_date')->orderBy('employee_id')->get();
        $totals = ['regular' => $entries->sum('regular_units'), 'overtime' => $entries->sum('overtime_units')];
        $rows = $entries->groupBy('employee_id')->map(function ($employeeEntries) {
            $first = $employeeEntries->first();
            $target = $first->employee->regular_hours_units ?: 800;
            return [
                'employee' => $first->employee,
                'days' => $employeeEntries->pluck('work_date')->map->format('Y-m-d')->unique()->count(),
                'first_date' => $employeeEntries->min('work_date'),
                'last_date' => $employeeEntries->max('work_date'),
                'regular' => $employeeEntries->sum('regular_units'),
                'overtime' => $employeeEntries->sum('overtime_units'),
                'approved' => $employeeEntries->where('status', 'approved')->count(),
                'pending' => $employeeEntries->where('status', 'pending')->count(),
                'rejected' => $employeeEntries->where('status', 'rejected')->count(),
                'short_days' => $employeeEntries->filter(fn($entry) => $entry->regular_units < $target)->count(),
                'shortfall' => $employeeEntries->sum(fn($entry) => max(0, $target - $entry->regular_units)),
            ];
        })->sortBy(function ($row) { return $row['employee']->name; })->values();
        $filters = ['status' => $request->status, 'employee_id' => $request->employee_id, 'company_id' => $request->company_id];
        $bytes = Documents::render('pdf.timesheet', compact('entries', 'rows', 'totals', 'month', 'workforce', 'filters'), 'A3', 'landscape');
        return Documents::download($bytes, ($workforce === 'own' ? 'attendance-' : 'timesheet-').$month.'.pdf');
    }
    public function employeePdfForm(Employee $employee)
    {
        Access::employee($employee);
        abort_unless($employee->employment_type === $this->workforce(), 404);
        $months = Timesheet::where('employee_id', $employee->id)->when(auth()->user()->isManager(),fn($q)=>$q->where('branch_id',auth()->user()->branch_id))->orderByDesc('work_date')->get('work_date')->map(function ($entry) {
            return $entry->work_date->format('Y-m');
        })->unique()->values();
        $routePrefix = $this->prefix();
        $employee->loadMissing(['company', 'designation']);
        return view('timesheets.employee-pdf-form', compact('employee', 'months', 'routePrefix'));
    }
    public function employeePdf(Request $request, Employee $employee)
    {
        Access::employee($employee);
        abort_unless($employee->employment_type === $this->workforce(), 404);
        $data = $request->validate([
            'months' => 'required|array|min:1|max:12',
            'months.*' => 'required|date_format:Y-m|distinct',
            'orientation' => 'required|in:portrait,landscape',
        ]);
        $available = Timesheet::where('employee_id', $employee->id)->when(auth()->user()->isManager(),fn($q)=>$q->where('branch_id',auth()->user()->branch_id))->get('work_date')->map(function ($entry) { return $entry->work_date->format('Y-m'); })->unique();
        $months = collect($data['months'])->unique()->sort()->values();
        if ($months->diff($available)->isNotEmpty()) { throw ValidationException::withMessages(['months' => 'Choose only months that contain timesheet entries for this employee.']); }
        $entries = Timesheet::with(['creator', 'reviewer'])->where('employee_id', $employee->id)->when(auth()->user()->isManager(),fn($q)=>$q->where('branch_id',auth()->user()->branch_id))->where(function ($query) use ($months) {
            foreach ($months as $month) { $query->orWhere('work_date', 'like', $month.'%'); }
        })->orderBy('work_date')->get();
        $sheets = $months->map(function ($month) use ($entries) {
            $monthEntries = $entries->filter(function ($entry) use ($month) { return $entry->work_date->format('Y-m') === $month; })->values();
            return ['month' => $month, 'entries' => $monthEntries, 'regular' => $monthEntries->sum('regular_units'), 'overtime' => $monthEntries->sum('overtime_units')];
        });
        $employee->loadMissing(['company', 'designation']);
        $orientation = $data['orientation'];
        $bytes = Documents::render('pdf.employee-timesheet', compact('employee', 'sheets', 'orientation'), 'A4', $orientation);
        return Documents::download($bytes, 'timesheet-'.$employee->id.'-'.$months->first().($months->count() > 1 ? '-to-'.$months->last() : '').'.pdf');
    }
    public function create() { return $this->form(new Timesheet(['work_date' => now(), 'regular_units' => 800, 'overtime_units' => 0])); }
    public function edit(Timesheet $timesheet)
    {
        $this->check($timesheet); $timesheet->loadMissing('employee');
        abort_unless($this->canCorrectHours($timesheet), 403, 'Only pending, payroll-unlocked entries can be edited by a company admin.');
        return $this->form($timesheet);
    }
    private function canCorrectHours(Timesheet $timesheet)
    {
        if ($timesheet->status === 'approved' || $timesheet->payroll_id) return false;
        if (auth()->user()->isSuperAdmin()) return true;
        return auth()->user()->isCompanyAdmin() && $timesheet->status === 'pending';
    }
    private function form($timesheet)
    {
        $workforce = $this->workforce(); $routePrefix = $this->prefix();
        $search = trim((string) request('employee_search'));
        $employees = Access::employees()->where('employment_type', $workforce)->where(function ($q) use ($timesheet) { $q->where('status', 'active')->orWhere('id', $timesheet->employee_id ?? 0); })->when(request('employee_id') && !$timesheet->exists, function ($q) { $q->where('id', request('employee_id')); })->when($search, function ($q) use ($search) { $q->where(function ($match) use ($search) { $match->where('name', 'like', '%'.$search.'%')->orWhere('iqama_number', 'like', '%'.$search.'%')->orWhereHas('company', function ($company) use ($search) { $company->where('name', 'like', '%'.$search.'%'); }); }); })->with('company')->orderBy('name')->limit(50)->get();
        return view('timesheets.form', compact('timesheet', 'employees', 'workforce', 'routePrefix'));
    }
    public function store(Request $request) { return $this->save($request, new Timesheet); }
    public function bulk()
    {
        $workforce = $this->workforce(); $routePrefix = $this->prefix();
        $search = trim((string) request('employee_search'));
        $employees = Access::employees()->where('employment_type', $workforce)->where('status', 'active')->when(request('employee_id'), function ($q) { $q->where('id', request('employee_id')); })->when($search, function ($q) use ($search) { $q->where(function ($match) use ($search) { $match->where('name', 'like', '%'.$search.'%')->orWhere('iqama_number', 'like', '%'.$search.'%')->orWhereHas('company', function ($company) use ($search) { $company->where('name', 'like', '%'.$search.'%'); }); }); })->with('company')->orderBy('name')->paginate(50)->withQueryString();
        return view('timesheets.bulk', compact('employees', 'workforce', 'routePrefix'));
    }
    public function storeBulk(Request $request)
    {
        $data = $request->validate([
            'employee_id' => 'required|integer|exists:employees,id',
            'total_hours' => ['nullable', 'numeric', 'min:0.01', 'max:2880', 'regex:/^\d+(\.\d{1,2})?$/'],
            'entries' => 'required|array|min:1|max:120',
            'entries.*.work_date' => 'required|date_format:Y-m-d|before_or_equal:today|distinct',
            'entries.*.regular_hours' => ['required', 'numeric', 'min:0', 'max:24', 'regex:/^\d+(\.\d{1,2})?$/'],
            'entries.*.overtime_hours' => ['required', 'numeric', 'min:0', 'max:24', 'regex:/^\d+(\.\d{1,2})?$/'],
            'notes' => 'nullable|string|max:1000',
        ]);
        $total = 0;
        foreach ($data['entries'] as $entry) {
            $hours = Pay::units($entry['regular_hours']) + Pay::units($entry['overtime_hours']);
            if ($hours > 2400) { throw ValidationException::withMessages(['entries' => 'Each date cannot exceed 24 total hours. Zero hours is allowed for an absence.']); }
            $total += $hours;
        }
        if (isset($data['total_hours']) && $data['total_hours'] !== '' && Pay::units($data['total_hours']) !== $total) {
            throw ValidationException::withMessages(['total_hours' => 'The date rows must add up to your total-hours target. Adjust the rows or clear the target.']);
        }
        DB::transaction(function () use ($data) {
            foreach ($data['entries'] as $entry) {
                $this->save(new Request(array_merge($entry, ['employee_id' => $data['employee_id'], 'notes' => $data['notes'] ?? null])), new Timesheet);
            }
        });
        return redirect()->route($this->prefix().'.index', ['month' => substr($data['entries'][array_key_first($data['entries'])]['work_date'], 0, 7), 'employee_id' => $data['employee_id']])
            ->with('success', count($data['entries']).' dates saved and submitted for Super Admin approval.');
    }
    public function update(Request $request, Timesheet $timesheet) { $this->check($timesheet); $timesheet->loadMissing('employee'); abort_unless($this->canCorrectHours($timesheet), 403, 'Only pending, payroll-unlocked entries can be edited by a company admin.'); return $this->save($request, $timesheet); }
    private function unlocked(Employee $employee, $date)
    {
        if (Payroll::where('employee_id', $employee->id)->where('month', substr($date, 0, 7))->exists()) { throw ValidationException::withMessages(['work_date' => 'Salary has already been generated for this employee and month. Void the draft payroll before changing entries.']); }
    }
    private function save(Request $request, Timesheet $timesheet)
    {
        $data = $request->validate([
            'employee_id' => 'required|integer|exists:employees,id',
            'work_date' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'regular_hours' => ['required', 'numeric', 'min:0', 'max:24', 'regex:/^\d+(\.\d{1,2})?$/'],
            'overtime_hours' => ['required', 'numeric', 'min:0', 'max:24', 'regex:/^\d+(\.\d{1,2})?$/'],
            'notes' => 'nullable|string|max:1000',
        ]);
        $regular = $this->workforce() === 'own' && $request->boolean('attendance_mode') ? ($request->boolean('attended') ? 800 : 0) : Pay::units($data['regular_hours']); $overtime = Pay::units($data['overtime_hours']);
        if ($regular + $overtime > 2400) { return back()->withErrors(['regular_hours' => 'Total hours cannot exceed 24 per day.'])->withInput(); }
        DB::transaction(function () use ($data, $timesheet, $regular, $overtime) {
            // The employee row serializes payroll generation and all timesheet changes.
            if ($timesheet->exists && $timesheet->employee_id != $data['employee_id']) { throw ValidationException::withMessages(['employee_id' => 'An existing entry cannot be moved to another employee.']); }
            $employee = Employee::lockForUpdate()->findOrFail($data['employee_id']); Access::employee($employee);
            abort_unless($employee->employment_type === $this->workforce(), 403);
            $dailyTarget = $employee->regular_hours_units ?: 800;
            $adjustedRegular = min($regular, $dailyTarget);
            $adjustedOvertime = $overtime + max(0, $regular - $dailyTarget);
            if ($timesheet->exists) { $timesheet = Timesheet::lockForUpdate()->findOrFail($timesheet->id); }
            $duplicate = Timesheet::where('employee_id', $employee->id)->whereDate('work_date', $data['work_date']);
            if ($timesheet->exists) { $duplicate->where('id', '!=', $timesheet->id); }
            if ($duplicate->exists()) { throw ValidationException::withMessages(['work_date' => 'An hours entry already exists for this employee and date. Edit the existing entry instead.']); }
            if ($timesheet->status === 'approved' || $timesheet->payroll_id) { throw ValidationException::withMessages(['work_date' => 'Approved or payroll-linked entries cannot be edited.']); }
            if ($employee->status !== 'active') { throw ValidationException::withMessages(['employee_id' => 'This employee is inactive.']); }
            if ($data['work_date'] < $employee->joined_on->format('Y-m-d')) { throw ValidationException::withMessages(['work_date' => 'Work date cannot be before the joining date.']); }
            $this->unlocked($employee, $data['work_date']);
            if ($timesheet->exists) { $this->unlocked($employee, $timesheet->work_date->format('Y-m-d')); }
            $timesheet->fill(['employee_id' => $employee->id, 'company_id'=>$employee->company_id, 'branch_id'=>$employee->branch_id, 'work_date' => $data['work_date'], 'regular_units' => $adjustedRegular, 'overtime_units' => $adjustedOvertime, 'notes' => $data['notes'] ?? null,
                'status' => 'pending', 'review_note' => null, 'reviewed_by' => null, 'reviewed_at' => null]);
            if (!$timesheet->exists) { $timesheet->fill(['created_by' => auth()->id(), 'hourly_rate_cents' => $employee->hourly_rate_cents, 'overtime_rate_cents' => $employee->overtime_rate_cents, 'overtime_multiplier_units' => $employee->overtime_multiplier_units]); }
            $timesheet->save(); ActivityLog::record('Saved hours', 'Timesheet #'.$timesheet->id.' · '.$employee->name.' · '.$data['work_date']);
        });
        return redirect()->route($this->prefix().'.index')->with('success', 'Hours saved and submitted for Super Admin approval.');
    }
    public function review(Request $request, Timesheet $timesheet)
    {
        $this->check($timesheet);
        $data = $request->validate(['status' => 'required|in:approved,rejected,pending', 'review_note' => 'required_if:status,rejected,pending|nullable|string|max:1000']);
        if ($data['status'] === 'pending') { abort_unless($request->user()->isSuperAdmin(), 403, 'Only the Super Admin can reopen approved timesheets.'); }
        DB::transaction(function () use ($timesheet, $data) {
            $employee = Employee::lockForUpdate()->findOrFail($timesheet->employee_id);
            $entry = Timesheet::lockForUpdate()->findOrFail($timesheet->id);
            $this->unlocked($employee, $entry->work_date->format('Y-m-d'));
            if ($data['status'] === 'pending') {
                if ($entry->status !== 'approved') { throw ValidationException::withMessages(['status' => 'Only approved entries can be reopened.']); }
                $entry->update(array_merge($data, ['reviewed_by' => null, 'reviewed_at' => null]));
            } else {
                if ($entry->status !== 'pending') { throw ValidationException::withMessages(['status' => 'Only pending entries can be reviewed.']); }
                $entry->update(array_merge($data, ['reviewed_by' => auth()->id(), 'reviewed_at' => now()]));
            }
            ActivityLog::record($data['status'] === 'pending' ? 'Reopened hours' : ucfirst($data['status']).' hours', 'Timesheet #'.$entry->id.' · '.$employee->name);
        });
        return back()->with('success', 'Entry '.$data['status'].'.');
    }
    public function bulkApprove(Request $request)
    {
        $data = $request->validate([
            'timesheet_ids' => 'required|array|min:1|max:200',
            'timesheet_ids.*' => 'required|integer|distinct|exists:timesheets,id',
        ], ['timesheet_ids.required' => 'Select at least one pending entry to approve.']);

        $ids = collect($data['timesheet_ids'])->map(function ($id) { return (int) $id; })->unique()->values();
        DB::transaction(function () use ($ids) {
            $entries = Timesheet::with('employee')->whereIn('id', $ids)->lockForUpdate()->get();
            if ($entries->count() !== $ids->count()) { throw ValidationException::withMessages(['timesheet_ids' => 'One or more selected entries no longer exist.']); }

            foreach ($entries as $entry) {
                $this->check($entry);
                if ($entry->status !== 'pending' || $entry->payroll_id) {
                    throw ValidationException::withMessages(['timesheet_ids' => 'Only pending, payroll-unlocked entries can be bulk approved. Refresh the page and try again.']);
                }
                $this->unlocked($entry->employee, $entry->work_date->format('Y-m-d'));
            }

            Timesheet::whereIn('id', $ids)->update(['status' => 'approved', 'review_note' => null, 'reviewed_by' => auth()->id(), 'reviewed_at' => now()]);
            ActivityLog::record('Bulk approved hours', $ids->count().' entries · Timesheets #'.$ids->take(20)->implode(', #'));
        });

        return back()->with('success', $ids->count().' time entries approved successfully.');
    }
    public function destroy(Timesheet $timesheet)
    {
        $this->check($timesheet);
        DB::transaction(function () use ($timesheet) {
            $employee = Employee::lockForUpdate()->findOrFail($timesheet->employee_id);
            $entry = Timesheet::lockForUpdate()->findOrFail($timesheet->id);
            $this->unlocked($employee, $entry->work_date->format('Y-m-d'));
            abort_unless(!$entry->payroll_id && (auth()->user()->isSuperAdmin() ? $entry->status !== 'approved' : auth()->user()->isCompanyAdmin() && $entry->status === 'pending'), 403);
            $entry->delete(); ActivityLog::record('Deleted hours', 'Timesheet #'.$entry->id.' · '.$employee->name);
        });
        return redirect()->route($this->prefix().'.index', ['month'=>$timesheet->work_date->format('Y-m'),'employee_id'=>$timesheet->employee_id])->with('success', 'Entry deleted.');
    }
}
