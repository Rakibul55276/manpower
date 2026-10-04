<?php
namespace App\Http\Controllers;
use App\Models\Timesheet;
use App\Models\Payroll;
use App\Services\Access;
use Illuminate\Http\Request;
class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $month = $request->validate(['month' => 'nullable|date_format:Y-m'])['month'] ?? now()->format('Y-m');
        $employees = Access::employees();
        $timesheets = Timesheet::whereIn('employee_id', Access::employees()->select('id'));
        $payrolls = Payroll::where('month', $month);
        if (!$request->user()->isAdmin()) { $payrolls->whereIn('company_id', $request->user()->companies()->select('companies.id')); }
        $stats = [
            'employees' => (clone $employees)->count(),
            'active' => (clone $employees)->where('status', 'active')->count(),
            'companies' => Access::companies()->count(),
            'pending' => (clone $timesheets)->where('status', 'pending')->where('work_date', 'like', $month.'%')->count(),
            'regular' => (clone $timesheets)->where('status', 'approved')->where('work_date', 'like', $month.'%')->sum('regular_units'),
            'overtime' => (clone $timesheets)->where('status', 'approved')->where('work_date', 'like', $month.'%')->sum('overtime_units'),
            'payroll' => (clone $payrolls)->sum('net_pay_cents'),
            'paid' => (clone $payrolls)->where('status', 'paid')->sum('net_pay_cents'),
        ];
        $recent = (clone $employees)->with(['company', 'designation'])->latest()->limit(5)->get();
        $pending = (clone $timesheets)->with('employee')->where('status', 'pending')->where('work_date', 'like', $month.'%')->orderBy('work_date')->limit(5)->get();
        $companies = Access::companies()->withCount(['employees' => function ($q) { $q->where('status', 'active'); }])->orderBy('name')->get();
        return view('dashboard', compact('month', 'stats', 'recent', 'pending', 'companies'));
    }
}
