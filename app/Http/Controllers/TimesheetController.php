<?php
namespace App\Http\Controllers;
use App\Models\Timesheet;
use App\Models\Employee;
use App\Models\Payroll;
use App\Models\ActivityLog;
use App\Services\Access;
use App\Services\Pay;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
class TimesheetController extends Controller
{
    private function workforce() { return request()->route('workforce') ?? 'rental'; }
    private function prefix() { return $this->workforce() === 'own' ? 'attendance' : 'timesheets'; }
    private function check(Timesheet $timesheet) { Access::employee($timesheet->employee); abort_unless($timesheet->employee->employment_type === $this->workforce(), 404); }
    public function index(Request $request)
    {
        $request->validate(['month' => 'nullable|date_format:Y-m', 'status' => 'nullable|in:pending,approved,rejected', 'employee_id' => 'nullable|integer', 'company_id' => 'nullable|integer']);
        $month = $request->month ?? now()->format('Y-m'); $workforce = $this->workforce(); $routePrefix = $this->prefix();
        $query = Timesheet::with(['employee.company', 'employee.designation', 'creator'])->whereIn('employee_id', Access::employees()->where('employment_type', $workforce)->select('id'))->where('work_date', 'like', $month.'%');
        if ($request->filled('status')) { $query->where('status', $request->status); }
        if ($request->filled('employee_id')) { $query->where('employee_id', $request->employee_id); }
        if ($request->filled('company_id')) { $query->whereHas('employee', function ($q) use ($request) { $q->where('company_id', $request->company_id); }); }
        $totals = ['regular' => (clone $query)->sum('regular_units'), 'overtime' => (clone $query)->sum('overtime_units')];
        $timesheets = $query->orderByDesc('work_date')->paginate(20)->withQueryString();
        $employees = Access::employees()->where('employment_type', $workforce)->orderBy('name')->get();
        $companies = Access::companies()->orderBy('name')->get();
        return view('timesheets.index', compact('timesheets', 'employees', 'companies', 'month', 'workforce', 'routePrefix', 'totals'));
    }
    public function create() { return $this->form(new Timesheet(['work_date' => now(), 'regular_units' => 800, 'overtime_units' => 0])); }
    public function edit(Timesheet $timesheet)
    {
        $this->check($timesheet); abort_unless($timesheet->status !== 'approved' && !$timesheet->payroll_id, 403, 'Approved or payroll-linked entries cannot be edited.');
        return $this->form($timesheet);
    }
    private function form($timesheet)
    {
        $workforce = $this->workforce(); $routePrefix = $this->prefix();
        $employees = Access::employees()->where('employment_type', $workforce)->where(function ($q) use ($timesheet) { $q->where('status', 'active')->orWhere('id', $timesheet->employee_id ?? 0); })->with('company')->orderBy('name')->get();
        return view('timesheets.form', compact('timesheet', 'employees', 'workforce', 'routePrefix'));
    }
    public function store(Request $request) { return $this->save($request, new Timesheet); }
    public function bulk()
    {
        $workforce = $this->workforce(); $routePrefix = $this->prefix();
        $employees = Access::employees()->where('employment_type', $workforce)->where('status', 'active')->with('company')->orderBy('name')->get();
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
            if ($hours <= 0 || $hours > 2400) { throw ValidationException::withMessages(['entries' => 'Each date must have more than zero and no more than 24 total hours.']); }
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
    public function update(Request $request, Timesheet $timesheet) { $this->check($timesheet); return $this->save($request, $timesheet); }
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
        $regular = Pay::units($data['regular_hours']); $overtime = Pay::units($data['overtime_hours']);
        if ($regular + $overtime === 0 || $regular + $overtime > 2400) { return back()->withErrors(['regular_hours' => 'Total hours must be greater than zero and no more than 24 per day.'])->withInput(); }
        DB::transaction(function () use ($data, $timesheet, $regular, $overtime) {
            // The employee row serializes payroll generation and all timesheet changes.
            if ($timesheet->exists && $timesheet->employee_id != $data['employee_id']) { throw ValidationException::withMessages(['employee_id' => 'An existing entry cannot be moved to another employee.']); }
            $employee = Employee::lockForUpdate()->findOrFail($data['employee_id']); Access::employee($employee);
            abort_unless($employee->employment_type === $this->workforce(), 403);
            if ($timesheet->exists) { $timesheet = Timesheet::lockForUpdate()->findOrFail($timesheet->id); }
            $duplicate = Timesheet::where('employee_id', $employee->id)->whereDate('work_date', $data['work_date']);
            if ($timesheet->exists) { $duplicate->where('id', '!=', $timesheet->id); }
            if ($duplicate->exists()) { throw ValidationException::withMessages(['work_date' => 'An hours entry already exists for this employee and date. Edit the existing entry instead.']); }
            if ($timesheet->status === 'approved' || $timesheet->payroll_id) { throw ValidationException::withMessages(['work_date' => 'Approved or payroll-linked entries cannot be edited.']); }
            if ($employee->status !== 'active') { throw ValidationException::withMessages(['employee_id' => 'This employee is inactive.']); }
            if ($data['work_date'] < $employee->joined_on->format('Y-m-d')) { throw ValidationException::withMessages(['work_date' => 'Work date cannot be before the joining date.']); }
            $this->unlocked($employee, $data['work_date']);
            if ($timesheet->exists) { $this->unlocked($employee, $timesheet->work_date->format('Y-m-d')); }
            $timesheet->fill(['employee_id' => $employee->id, 'work_date' => $data['work_date'], 'regular_units' => $regular, 'overtime_units' => $overtime, 'notes' => $data['notes'] ?? null,
                'status' => 'pending', 'review_note' => null, 'reviewed_by' => null, 'reviewed_at' => null]);
            if (!$timesheet->exists) { $timesheet->fill(['created_by' => auth()->id(), 'hourly_rate_cents' => $employee->hourly_rate_cents, 'overtime_multiplier_units' => $employee->overtime_multiplier_units]); }
            $timesheet->save(); ActivityLog::record('Saved hours', 'Timesheet #'.$timesheet->id.' · '.$employee->name.' · '.$data['work_date']);
        });
        return redirect()->route($this->prefix().'.index')->with('success', 'Hours saved and submitted for Super Admin approval.');
    }
    public function review(Request $request, Timesheet $timesheet)
    {
        $this->check($timesheet);
        $data = $request->validate(['status' => 'required|in:approved,rejected,pending', 'review_note' => 'required_if:status,rejected,pending|nullable|string|max:1000']);
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
    public function destroy(Timesheet $timesheet)
    {
        $this->check($timesheet);
        DB::transaction(function () use ($timesheet) {
            $employee = Employee::lockForUpdate()->findOrFail($timesheet->employee_id);
            $entry = Timesheet::lockForUpdate()->findOrFail($timesheet->id);
            $this->unlocked($employee, $entry->work_date->format('Y-m-d'));
            abort_unless($entry->status !== 'approved' && !$entry->payroll_id, 403);
            $entry->delete(); ActivityLog::record('Deleted hours', 'Timesheet #'.$entry->id.' · '.$employee->name);
        });
        return back()->with('success', 'Entry deleted.');
    }
}
