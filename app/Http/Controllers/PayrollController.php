<?php
namespace App\Http\Controllers;
use App\Models\Employee;
use App\Models\Payroll;
use App\Models\Timesheet;
use App\Models\ActivityLog;
use App\Services\Access;
use App\Services\Pay;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
class PayrollController extends Controller
{
    private function workforce() { return request()->route('workforce') ?? 'rental'; }
    private function prefix() { return $this->workforce() === 'own' ? 'salaries' : 'payrolls'; }
    private function check(Payroll $payroll) { Access::company($payroll->company_id); abort_unless($payroll->employment_type === $this->workforce(), 404); }
    public function index(Request $request)
    {
        $request->validate(['month' => 'nullable|date_format:Y-m', 'status' => 'nullable|in:pending,approved,paid', 'company_id' => 'nullable|integer', 'search' => 'nullable|string|max:100']);
        $workforce = $this->workforce(); $routePrefix = $this->prefix();
        $available = Payroll::where('employment_type', $workforce);
        if (!$request->user()->isAdmin()) { $available->whereIn('company_id', $request->user()->companies()->select('companies.id')); }
        $month = $request->month ?? $available->max('month') ?? now()->format('Y-m');
        $query = Payroll::where('employment_type', $workforce)->where('month', $month);
        if (!$request->user()->isAdmin()) { $query->whereIn('company_id', $request->user()->companies()->select('companies.id')); }
        if ($request->filled('status')) { $query->where('status', $request->status); }
        if ($request->filled('company_id')) { $query->where('company_id', $request->company_id); }
        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($match) use ($search) {
                $match->where('employee_name', 'like', '%'.$search.'%')
                    ->orWhere('iqama_number', 'like', '%'.$search.'%')
                    ->orWhere('company_name', 'like', '%'.$search.'%')
                    ->orWhere('designation_name', 'like', '%'.$search.'%');
            });
        }
        $total = (clone $query)->sum('net_pay_cents');
        $payrolls = $query->orderBy('employee_name')->paginate(20)->withQueryString();
        $employeeQuery = Access::employees()->where('employment_type', $workforce)->with(['company', 'designation'])->orderBy('name');
        if ($request->filled('company_id')) { $employeeQuery->where('company_id', (int) $request->company_id); }
        if ($request->filled('search')) {
            $search = trim($request->search);
            $employeeQuery->where(function ($match) use ($search) { $match->where('name', 'like', '%'.$search.'%')->orWhere('iqama_number', 'like', '%'.$search.'%')->orWhereHas('company', function ($company) use ($search) { $company->where('name', 'like', '%'.$search.'%'); })->orWhereHas('designation', function ($designation) use ($search) { $designation->where('name', 'like', '%'.$search.'%'); }); });
        }
        $directoryEmployees = $employeeQuery->paginate(25, ['*'], 'directory_page')->withQueryString();
        $directoryPayrollQuery = Payroll::where('employment_type', $workforce)->where('month', $month);
        if (!$request->user()->isAdmin()) { $directoryPayrollQuery->whereIn('company_id', $request->user()->companies()->select('companies.id')); }
        $directoryPayrolls = $directoryPayrollQuery->whereIn('employee_id', $directoryEmployees->pluck('id'))->get()->keyBy('employee_id');
        $pendingByEmployee = Timesheet::whereIn('employee_id', $directoryEmployees->pluck('id'))->where('work_date', 'like', $month.'%')->where('status', 'pending')->selectRaw('employee_id, COUNT(*) as pending_count')->groupBy('employee_id')->pluck('pending_count', 'employee_id');
        $payrollDirectory = $directoryEmployees->getCollection()->map(function ($employee) use ($directoryPayrolls, $pendingByEmployee) {
            return ['employee' => $employee, 'payroll' => $directoryPayrolls->get($employee->id), 'pending' => (int) ($pendingByEmployee[$employee->id] ?? 0)];
        })->values();
        $employees = Access::employees()->where('employment_type', $workforce)->where('status', 'active')->with('company')->when($request->filled('search'), function ($employeeQuery) use ($request) { $search = trim($request->search); $employeeQuery->where(function ($match) use ($search) { $match->where('name', 'like', '%'.$search.'%')->orWhere('iqama_number', 'like', '%'.$search.'%')->orWhereHas('company', function ($company) use ($search) { $company->where('name', 'like', '%'.$search.'%'); }); }); })->orderBy('name')->limit(50)->get();
        $companies = Access::companies()->orderBy('name')->get();
        return view('payrolls.index', compact('month', 'workforce', 'routePrefix', 'payrolls', 'employees', 'companies', 'total', 'payrollDirectory', 'directoryEmployees'));
    }
    public function store(Request $request)
    {
        $data = $request->validate(['employee_id' => 'required|exists:employees,id', 'month' => 'required|date_format:Y-m', 'allowance' => ['required', 'numeric', 'min:0', 'max:999999.99', 'regex:/^\d+(\.\d{1,2})?$/'], 'deduction' => ['required', 'numeric', 'min:0', 'max:999999.99', 'regex:/^\d+(\.\d{1,2})?$/'], 'notes' => 'nullable|string|max:2000']);
        if ($data['month'] > now()->format('Y-m')) { return back()->withErrors(['month' => 'Payroll cannot be generated for a future month.'])->withInput(); }
        $payroll = DB::transaction(function () use ($data) {
            $employee = Employee::with(['company', 'designation'])->lockForUpdate()->findOrFail($data['employee_id']); Access::employee($employee);
            abort_unless($employee->employment_type === $this->workforce(), 403);
            if ($employee->joined_on->format('Y-m') > $data['month']) { throw ValidationException::withMessages(['month' => 'The employee had not joined in this month.']); }
            if (Payroll::where('employee_id', $employee->id)->where('month', $data['month'])->exists()) { throw ValidationException::withMessages(['month' => 'Payroll already exists for this employee and month.']); }
            $entries = Timesheet::where('employee_id', $employee->id)->where('work_date', 'like', $data['month'].'%')->lockForUpdate()->get();
            $pending = $entries->where('status', 'pending')->sortBy('work_date');
            if ($pending->isNotEmpty()) {
                $dates = $pending->take(5)->map(function ($entry) { return $entry->work_date->format('d M'); })->implode(', ');
                $more = $pending->count() > 5 ? ' and '.($pending->count() - 5).' more' : '';
                throw ValidationException::withMessages(['month' => $pending->count().' pending '.($pending->count() === 1 ? 'day' : 'days').' must be reviewed: '.$dates.$more.'.']);
            }
            $approved = $entries->where('status', 'approved');
            if ($employee->employment_type === 'rental' && $approved->isEmpty()) { throw ValidationException::withMessages(['month' => 'Rental payroll requires approved timesheet hours.']); }
            $base = $employee->employment_type === 'own' ? $employee->monthly_salary_cents : $approved->sum(function ($entry) { return $entry->regularPay(); });
            $overtime = $approved->sum(function ($entry) { return $entry->overtimePay(); });
            $allowance = Pay::units($data['allowance']); $deduction = Pay::units($data['deduction']);
            $meal = $employee->employment_type === 'own' ? $employee->meal_allowance_cents : 0;
            $transportation = $employee->employment_type === 'own' ? $employee->transportation_allowance_cents : 0;
            $housing = $employee->employment_type === 'own' ? $employee->housing_allowance_cents : 0;
            $medical = $employee->employment_type === 'own' ? $employee->medical_allowance_cents : 0;
            $retirement = $employee->employment_type === 'own' ? $employee->retirement_insurance_cents : 0;
            $tax = $employee->employment_type === 'own' ? $employee->tax_cents : 0;
            $gross = $base + $overtime + $allowance + $meal + $transportation + $housing + $medical;
            $totalDeductions = $deduction + $retirement + $tax;
            if ($totalDeductions > $gross) { throw ValidationException::withMessages(['deduction' => 'Total deductions cannot exceed gross salary plus allowances.']); }
            $payroll = Payroll::create(['employee_id' => $employee->id, 'company_id' => $employee->company_id, 'month' => $data['month'], 'employment_type' => $employee->employment_type, 'salary_type' => $employee->salary_type,
                'employee_name' => $employee->name, 'company_name' => $employee->company->name, 'designation_name' => $employee->designation->name, 'directorate' => $employee->directorate, 'department' => $employee->department, 'iqama_number' => $employee->iqama_number,
                'regular_units' => $approved->sum('regular_units'), 'overtime_units' => $approved->sum('overtime_units'), 'regular_pay_cents' => $base, 'overtime_pay_cents' => $overtime,
                'allowance_cents' => $allowance, 'meal_allowance_cents' => $meal, 'transportation_allowance_cents' => $transportation, 'housing_allowance_cents' => $housing, 'medical_allowance_cents' => $medical,
                'deduction_cents' => $deduction, 'retirement_insurance_cents' => $retirement, 'tax_cents' => $tax, 'net_pay_cents' => $gross - $totalDeductions, 'status' => 'pending', 'created_by' => auth()->id(), 'notes' => $data['notes'] ?? null]);
            Timesheet::whereIn('id', $approved->pluck('id'))->update(['payroll_id' => $payroll->id]);
            ActivityLog::record('Generated salary', 'Payslip #'.$payroll->id.' · '.$employee->name.' · '.$data['month']);
            return $payroll;
        });
        return redirect()->route($this->prefix().'.show', $payroll)->with('success', 'Draft salary generated. Hours are now locked for this employee and month.');
    }
    public function show(Payroll $payroll)
    {
        $this->check($payroll); $payroll->load('timesheets');
        $workforce = $this->workforce(); $routePrefix = $this->prefix();
        return view('payrolls.show', compact('payroll', 'workforce', 'routePrefix'));
    }
    public function update(Request $request, Payroll $payroll)
    {
        $this->check($payroll);
        $data = $request->validate(['allowance' => ['required', 'numeric', 'min:0', 'max:999999.99', 'regex:/^\d+(\.\d{1,2})?$/'], 'deduction' => ['required', 'numeric', 'min:0', 'max:999999.99', 'regex:/^\d+(\.\d{1,2})?$/'], 'notes' => 'nullable|string|max:2000']);
        DB::transaction(function () use ($payroll, $data) {
            $record = Payroll::lockForUpdate()->findOrFail($payroll->id);
            abort_unless($record->status === 'pending', 403, 'Only pending salaries can be adjusted.');
            $allowance = Pay::units($data['allowance']); $deduction = Pay::units($data['deduction']);
            $gross = $record->regular_pay_cents + $record->overtime_pay_cents + $allowance + $record->meal_allowance_cents + $record->transportation_allowance_cents + $record->housing_allowance_cents + $record->medical_allowance_cents;
            $totalDeductions = $deduction + $record->retirement_insurance_cents + $record->tax_cents;
            if ($totalDeductions > $gross) { throw ValidationException::withMessages(['deduction' => 'Total deductions cannot exceed gross pay.']); }
            $record->update(['allowance_cents' => $allowance, 'deduction_cents' => $deduction, 'net_pay_cents' => $gross - $totalDeductions, 'notes' => $data['notes'] ?? null]);
            ActivityLog::record('Adjusted salary', 'Payslip #'.$record->id);
        });
        return back()->with('success', 'Salary adjustments saved.');
    }
    public function paid(Payroll $payroll)
    {
        $this->check($payroll);
        DB::transaction(function () use ($payroll) {
            $record = Payroll::lockForUpdate()->findOrFail($payroll->id); abort_unless($record->status === 'approved', 403);
            $record->update(['status' => 'paid', 'paid_at' => now(), 'paid_by' => auth()->id()]);
            ActivityLog::record('Marked salary paid', 'Payslip #'.$record->id.' · '.$record->employee_name);
        });
        return back()->with('success', 'Salary marked paid. This is a payment record; no bank transfer is made.');
    }
    public function approve(Payroll $payroll)
    {
        $this->check($payroll);
        DB::transaction(function () use ($payroll) {
            $record = Payroll::lockForUpdate()->findOrFail($payroll->id);
            abort_unless($record->status === 'pending', 403, 'Only pending salaries can be approved.');
            $record->update(['status' => 'approved', 'approved_by' => auth()->id(), 'approved_at' => now()]);
            ActivityLog::record('Approved salary', 'Payslip #'.$record->id.' · '.$record->employee_name);
        });
        return back()->with('success', 'Salary approved and ready for payment.');
    }
    public function bulkApprove(Request $request)
    {
        $data = $request->validate(['payroll_ids' => 'required|array|min:1|max:100', 'payroll_ids.*' => 'required|integer|distinct|exists:payrolls,id']);
        $approved = DB::transaction(function () use ($data) {
            $records = Payroll::whereIn('id', $data['payroll_ids'])->lockForUpdate()->get();
            abort_unless($records->count() === count($data['payroll_ids']), 422);
            foreach ($records as $record) {
                $this->check($record);
                abort_unless($record->status === 'pending', 403, 'Only pending salaries can be approved.');
                $record->update(['status' => 'approved', 'approved_by' => auth()->id(), 'approved_at' => now()]);
                ActivityLog::record('Approved salary', 'Payslip #'.$record->id.' · '.$record->employee_name.' · Bulk approval');
            }
            return $records->count();
        });
        return back()->with('success', $approved.' '.($approved === 1 ? 'salary' : 'salaries').' approved and ready to print.');
    }
    public function destroy(Payroll $payroll)
    {
        $this->check($payroll);
        DB::transaction(function () use ($payroll) {
            Employee::lockForUpdate()->findOrFail($payroll->employee_id);
            $record = Payroll::lockForUpdate()->findOrFail($payroll->id); abort_unless($record->status === 'pending', 403, 'Only pending salaries can be voided.');
            $record->timesheets()->update(['payroll_id' => null]); $record->delete();
            ActivityLog::record('Voided draft salary', 'Payslip #'.$record->id.' · '.$record->employee_name);
        });
        return redirect()->route($this->prefix().'.index')->with('success', 'Draft voided. Timesheet entry for this month is unlocked.');
    }
    public function export(Request $request)
    {
        $data = $request->validate(['month' => 'required|date_format:Y-m']);
        $query = Payroll::where('month', $data['month'])->where('employment_type', $this->workforce());
        if (!$request->user()->isAdmin()) { $query->whereIn('company_id', $request->user()->companies()->select('companies.id')); }
        $rows = $query->orderBy('employee_name')->get(); $workforce = $this->workforce();
        return response()->streamDownload(function () use ($rows) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Month', 'Employee', 'Company', 'Designation', 'Iqama', 'Salary type', 'Regular hours', 'Overtime hours', 'Base pay SAR', 'Overtime pay SAR', 'Allowance SAR', 'Deduction SAR', 'Net pay SAR', 'Status']);
            foreach ($rows as $row) {
                $cells = [$row->month, $row->employee_name, $row->company_name, $row->designation_name, $row->iqama_number, $row->salary_type, Pay::decimal($row->regular_units), Pay::decimal($row->overtime_units), Pay::decimal($row->regular_pay_cents), Pay::decimal($row->overtime_pay_cents), Pay::decimal($row->allowance_cents), Pay::decimal($row->deduction_cents), Pay::decimal($row->net_pay_cents), $row->status];
                $cells = array_map(function ($cell) { return preg_match('/^[=+\-@\t\r\n]/', (string) $cell) ? "'".$cell : $cell; }, $cells);
                fputcsv($file, $cells);
            }
            fclose($file);
        }, $workforce.'-payroll-'.$data['month'].'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
    public function pdf(Payroll $payroll)
    {
        $this->check($payroll);
        if ($payroll->status === 'pending') { return back()->withErrors(['payslip' => 'Waiting for approval. The salary slip can be printed only after Admin approval.']); }
        return \App\Services\Documents::download(\App\Services\Documents::payslip($payroll), 'payslip-'.$payroll->id.'-'.$payroll->month.'.pdf');
    }
}
