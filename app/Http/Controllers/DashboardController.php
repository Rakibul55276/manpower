<?php
namespace App\Http\Controllers;
use App\Models\Timesheet;
use App\Models\Payroll;
use App\Services\Access;
use App\Modules\Invoicing\Models\Invoice;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $month = $request->validate(['month' => 'nullable|date_format:Y-m'])['month'] ?? now()->format('Y-m');
        $period = Carbon::createFromFormat('!Y-m', $month);
        $previousMonth = $period->copy()->subMonth()->format('Y-m');
        $employees = Access::employees();
        $employeeIds = Access::employees()->select('id');
        $timesheets = Timesheet::whereIn('employee_id', $employeeIds);
        $payrolls = Payroll::where('month', $month);
        if (!$request->user()->isAdmin()) { $payrolls->whereIn('company_id', $request->user()->companies()->select('companies.id')); }
        $previousPayrolls = Payroll::where('month', $previousMonth);
        if (!$request->user()->isAdmin()) { $previousPayrolls->whereIn('company_id', $request->user()->companies()->select('companies.id')); }
        $monthStart = $period->copy()->startOfMonth()->format('Y-m-d'); $monthEnd = $period->copy()->endOfMonth()->format('Y-m-d');
        $previousStart = $period->copy()->subMonth()->startOfMonth()->format('Y-m-d'); $previousEnd = $period->copy()->subMonth()->endOfMonth()->format('Y-m-d');
        $stats = [
            'employees' => (clone $employees)->count(),
            'active' => (clone $employees)->where('status', 'active')->count(),
            'companies' => Access::companies()->count(),
            'rental' => (clone $employees)->where('employment_type', 'rental')->where('status', 'active')->count(),
            'own' => (clone $employees)->where('employment_type', 'own')->where('status', 'active')->count(),
            'pending' => (clone $timesheets)->where('status', 'pending')->whereBetween('work_date', [$monthStart, $monthEnd])->count(),
            'regular' => (clone $timesheets)->where('status', 'approved')->whereBetween('work_date', [$monthStart, $monthEnd])->sum('regular_units'),
            'overtime' => (clone $timesheets)->where('status', 'approved')->whereBetween('work_date', [$monthStart, $monthEnd])->sum('overtime_units'),
            'payroll' => (clone $payrolls)->sum('net_pay_cents'),
            'paid' => (clone $payrolls)->where('status', 'paid')->sum('net_pay_cents'),
            'payroll_pending' => (clone $payrolls)->where('status', 'pending')->count(),
            'payroll_approved' => (clone $payrolls)->where('status', 'approved')->count(),
        ];
        $previous = [
            'regular' => (clone $timesheets)->where('status', 'approved')->whereBetween('work_date', [$previousStart, $previousEnd])->sum('regular_units'),
            'overtime' => (clone $timesheets)->where('status', 'approved')->whereBetween('work_date', [$previousStart, $previousEnd])->sum('overtime_units'),
            'payroll' => (clone $previousPayrolls)->sum('net_pay_cents'),
        ];
        $change = function ($current, $prior) { return $prior > 0 ? round((($current - $prior) / $prior) * 100, 1) : ($current > 0 ? 100.0 : 0.0); };
        $changes = ['regular' => $change($stats['regular'], $previous['regular']), 'overtime' => $change($stats['overtime'], $previous['overtime']), 'payroll' => $change($stats['payroll'], $previous['payroll'])];
        $recent = (clone $employees)->with(['company', 'designation'])->latest()->limit(5)->get();
        $pending = (clone $timesheets)->with('employee')->where('status', 'pending')->whereBetween('work_date', [$monthStart, $monthEnd])->orderBy('work_date')->limit(5)->get();
        $companies = Access::companies()->withCount(['employees' => function ($q) { $q->where('status', 'active'); }])->orderBy('name')->get();
        $companyIds = $companies->pluck('id');
        $companyPayroll = Payroll::where('month', $month)->whereIn('company_id', $companyIds)->selectRaw('company_id, SUM(net_pay_cents) total')->groupBy('company_id')->pluck('total', 'company_id');
        $companyPending = Timesheet::join('employees', 'employees.id', '=', 'timesheets.employee_id')->whereIn('employees.company_id', $companyIds)->where('timesheets.status', 'pending')->whereBetween('timesheets.work_date', [$monthStart, $monthEnd])->selectRaw('employees.company_id, COUNT(*) total')->groupBy('employees.company_id')->pluck('total', 'employees.company_id');
        $companyAnalytics = $companies->map(function ($company) use ($companyPayroll, $companyPending) { return ['company'=>$company,'employees'=>$company->employees_count,'payroll'=>(int)($companyPayroll[$company->id]??0),'pending'=>(int)($companyPending[$company->id]??0)]; })->sortByDesc('employees')->take(8)->values();
        $trend = collect(range(5, 0))->map(function ($offset) use ($period, $request) {
            $point = $period->copy()->subMonths($offset); $key = $point->format('Y-m'); $start=$point->copy()->startOfMonth()->format('Y-m-d'); $end=$point->copy()->endOfMonth()->format('Y-m-d');
            $hours = Timesheet::whereIn('employee_id', Access::employees()->select('id'))->where('status','approved')->whereBetween('work_date',[$start,$end])->sum(DB::raw('regular_units + overtime_units'));
            $pay = Payroll::where('month',$key); if(!$request->user()->isAdmin())$pay->whereIn('company_id',$request->user()->companies()->select('companies.id'));
            return ['month'=>$key,'label'=>$point->format('M'),'hours'=>(int)$hours,'payroll'=>(int)$pay->sum('net_pay_cents')];
        });
        $maxHours=max(1,(int)$trend->max('hours')); $maxPayroll=max(1,(int)$trend->max('payroll'));
        $invoiceStats = ['draft'=>Invoice::where('status','draft')->count(),'approved'=>Invoice::where('status','approved')->count(),'paid'=>Invoice::where('status','paid')->count(),'value'=>Invoice::whereIn('status',['approved','paid'])->sum('total_cents')];
        return view('dashboard', compact('month', 'stats', 'previous', 'changes', 'recent', 'pending', 'companies', 'companyAnalytics', 'trend', 'maxHours', 'maxPayroll', 'invoiceStats'));
    }
}
