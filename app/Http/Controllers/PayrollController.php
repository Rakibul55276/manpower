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
        $request->validate(['month' => 'nullable|date_format:Y-m', 'status' => 'nullable|in:draft,paid', 'company_id' => 'nullable|integer']);
        $workforce = $this->workforce(); $routePrefix = $this->prefix();
        $available = Payroll::where('employment_type', $workforce);
        if (!$request->user()->isAdmin()) { $available->whereIn('company_id', $request->user()->companies()->select('companies.id')); }
        $month = $request->month ?? $available->max('month') ?? now()->format('Y-m');
        $query = Payroll::where('employment_type', $workforce)->where('month', $month);
        if (!$request->user()->isAdmin()) { $query->whereIn('company_id', $request->user()->companies()->select('companies.id')); }
        if ($request->filled('status')) { $query->where('status', $request->status); }
        if ($request->filled('company_id')) { $query->where('company_id', $request->company_id); }
        $total = (clone $query)->sum('net_pay_cents');
        $payrolls = $query->orderBy('employee_name')->paginate(20)->withQueryString();
        $employees = Access::employees()->where('employment_type', $workforce)->with('company')->orderBy('name')->get();
        $companies = Access::companies()->orderBy('name')->get();
        return view('payrolls.index', compact('month', 'workforce', 'routePrefix', 'payrolls', 'employees', 'companies', 'total'));
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
            if ($entries->where('status', 'pending')->isNotEmpty()) { throw ValidationException::withMessages(['month' => 'Review all pending entries for this employee and month before generating payroll.']); }
            $approved = $entries->where('status', 'approved');
            if ($employee->employment_type === 'rental' && $approved->isEmpty()) { throw ValidationException::withMessages(['month' => 'Rental payroll requires approved timesheet hours.']); }
            $base = $employee->employment_type === 'own' ? $employee->monthly_salary_cents : $approved->sum(function ($entry) { return $entry->regularPay(); });
            $overtime = $approved->sum(function ($entry) { return $entry->overtimePay(); });
            $allowance = Pay::units($data['allowance']); $deduction = Pay::units($data['deduction']);
            if ($deduction > $base + $overtime + $allowance) { throw ValidationException::withMessages(['deduction' => 'Deductions cannot exceed gross salary plus allowances.']); }
            $payroll = Payroll::create(['employee_id' => $employee->id, 'company_id' => $employee->company_id, 'month' => $data['month'], 'employment_type' => $employee->employment_type, 'salary_type' => $employee->salary_type,
                'employee_name' => $employee->name, 'company_name' => $employee->company->name, 'designation_name' => $employee->designation->name, 'iqama_number' => $employee->iqama_number,
                'regular_units' => $approved->sum('regular_units'), 'overtime_units' => $approved->sum('overtime_units'), 'regular_pay_cents' => $base, 'overtime_pay_cents' => $overtime,
                'allowance_cents' => $allowance, 'deduction_cents' => $deduction, 'net_pay_cents' => $base + $overtime + $allowance - $deduction, 'created_by' => auth()->id(), 'notes' => $data['notes'] ?? null]);
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
            abort_unless($record->status === 'draft', 403, 'Paid salaries are immutable.');
            $allowance = Pay::units($data['allowance']); $deduction = Pay::units($data['deduction']);
            $gross = $record->regular_pay_cents + $record->overtime_pay_cents + $allowance;
            if ($deduction > $gross) { throw ValidationException::withMessages(['deduction' => 'Deductions cannot exceed gross pay.']); }
            $record->update(['allowance_cents' => $allowance, 'deduction_cents' => $deduction, 'net_pay_cents' => $gross - $deduction, 'notes' => $data['notes'] ?? null]);
            ActivityLog::record('Adjusted salary', 'Payslip #'.$record->id);
        });
        return back()->with('success', 'Salary adjustments saved.');
    }
    public function paid(Payroll $payroll)
    {
        $this->check($payroll);
        DB::transaction(function () use ($payroll) {
            $record = Payroll::lockForUpdate()->findOrFail($payroll->id); abort_unless($record->status === 'draft', 403);
            $record->update(['status' => 'paid', 'paid_at' => now(), 'paid_by' => auth()->id()]);
            ActivityLog::record('Marked salary paid', 'Payslip #'.$record->id.' · '.$record->employee_name);
        });
        return back()->with('success', 'Salary marked paid. This is a payment record; no bank transfer is made.');
    }
    public function destroy(Payroll $payroll)
    {
        $this->check($payroll);
        DB::transaction(function () use ($payroll) {
            Employee::lockForUpdate()->findOrFail($payroll->employee_id);
            $record = Payroll::lockForUpdate()->findOrFail($payroll->id); abort_unless($record->status === 'draft', 403, 'Paid salaries cannot be voided.');
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
        return \App\Services\Documents::download(\App\Services\Documents::payslip($payroll), 'payslip-'.$payroll->id.'-'.$payroll->month.'.pdf');
    }
}
