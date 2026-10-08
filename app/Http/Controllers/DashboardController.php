<?php
namespace App\Http\Controllers;
use App\Models\Timesheet;
use App\Models\Payroll;
use App\Services\Access;
use App\Modules\Invoicing\Models\Invoice;
use App\Modules\SafetyShop\Reports\Services\FinancialReport;
use App\Modules\SafetyShop\Products\Models\Product;
use App\Modules\SafetyShop\Stock\Models\Stock;
use App\Modules\SafetyShop\Stock\Models\Movement;
use App\Modules\SafetyShop\Sales\Models\Customer as SafetyShopCustomer;
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
        $timesheets = Timesheet::whereIn('employee_id', $employeeIds); if(!$request->user()->isSuperAdmin())$timesheets->where('company_id',$request->user()->company_id); if($request->user()->isManager())$timesheets->where('branch_id',$request->user()->branch_id);
        $payrolls = Payroll::where('month', $month)->whereIn('employee_id', $employeeIds);
        $previousPayrolls = Payroll::where('month', $previousMonth)->whereIn('employee_id', $employeeIds);
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
        $companies = Access::companies()->withCount(['employees' => function ($q) use ($request) { $q->where('status', 'active'); if($request->user()->isManager())$q->where('branch_id',$request->user()->branch_id); }])->orderBy('name')->get();
        $companyIds = $companies->pluck('id');
        $companyPayroll = Payroll::where('month', $month)->whereIn('employee_id',$employeeIds)->whereIn('company_id', $companyIds)->selectRaw('company_id, SUM(net_pay_cents) total')->groupBy('company_id')->pluck('total', 'company_id');
        $companyPendingQuery = Timesheet::join('employees', 'employees.id', '=', 'timesheets.employee_id')->whereIn('employees.id',$employeeIds)->whereIn('employees.company_id', $companyIds)->where('timesheets.status', 'pending')->whereBetween('timesheets.work_date', [$monthStart, $monthEnd]); if($request->user()->isManager())$companyPendingQuery->where('timesheets.branch_id',$request->user()->branch_id); $companyPending=$companyPendingQuery->selectRaw('employees.company_id, COUNT(*) total')->groupBy('employees.company_id')->pluck('total', 'employees.company_id');
        $companyAnalytics = $companies->map(function ($company) use ($companyPayroll, $companyPending) { return ['company'=>$company,'employees'=>$company->employees_count,'payroll'=>(int)($companyPayroll[$company->id]??0),'pending'=>(int)($companyPending[$company->id]??0)]; })->sortByDesc('employees')->take(8)->values();
        $trend = collect(range(5, 0))->map(function ($offset) use ($period, $request) {
            $point = $period->copy()->subMonths($offset); $key = $point->format('Y-m'); $start=$point->copy()->startOfMonth()->format('Y-m-d'); $end=$point->copy()->endOfMonth()->format('Y-m-d');
            $hoursQuery = Timesheet::whereIn('employee_id', Access::employees()->select('id'))->where('status','approved')->whereBetween('work_date',[$start,$end]); if(!$request->user()->isSuperAdmin())$hoursQuery->where('company_id',$request->user()->company_id); if($request->user()->isManager())$hoursQuery->where('branch_id',$request->user()->branch_id); $hours=$hoursQuery->sum(DB::raw('regular_units + overtime_units'));
            $pay = Payroll::where('month',$key)->whereIn('employee_id',Access::employees()->select('id'));
            return ['month'=>$key,'label'=>$point->format('M'),'hours'=>(int)$hours,'payroll'=>(int)$pay->sum('net_pay_cents')];
        });
        $maxHours=max(1,(int)$trend->max('hours')); $maxPayroll=max(1,(int)$trend->max('payroll'));
        $invoiceStats = [];
        if (config('invoicing.enabled') && $request->user()->isSuperAdmin()) {
            $invoiceStats = ['draft'=>Invoice::where('status','draft')->count(),'approved'=>Invoice::where('status','approved')->count(),'paid'=>Invoice::where('status','paid')->count(),'value'=>Invoice::whereIn('status',['approved','paid'])->sum('total_cents')];
        }
        $safetyShopAnalytics=[];
        $safetyShopStockAnalytics=[];
        if(config('safety_shop.enabled') && $request->user()->isSuperAdmin()){
            $financial=app(FinancialReport::class)->build(['from'=>$monthStart,'to'=>$monthEnd]);
            $safetyShopAnalytics=$financial['summary']+[
                'products'=>Product::where('is_active',true)->count(),
                'units'=>(int)Stock::sum('quantity'),
                'stock_value'=>(int)Stock::join('safety_shop_products','product_id','=','safety_shop_products.id')->sum(DB::raw('quantity * cost_cents')),
                'low_stock'=>Product::where('is_active',true)->whereRaw('(SELECT COALESCE(SUM(quantity),0) FROM safety_shop_stocks WHERE product_id = safety_shop_products.id) <= reorder_level')->count(),
                'customers'=>SafetyShopCustomer::where('is_active',true)->count(),
            ];
        }
        if(config('safety_shop.enabled') && $request->user()->isCompanyAdmin()){
            $companyId=$request->user()->company_id;
            $financial=app(FinancialReport::class)->build(['from'=>$monthStart,'to'=>$monthEnd,'company_id'=>$companyId]);
            $safetyShopAnalytics=$financial['summary'];
            $safetyShopStockAnalytics=[
                'net_revenue'=>(int)$financial['summary']['net_revenue'],
                'products'=>Product::where('company_id',$companyId)->where('is_active',true)->count(),
                'units'=>(int)Stock::whereHas('product',fn($q)=>$q->where('company_id',$companyId))->sum('quantity'),
                'stock_value'=>(int)Stock::join('safety_shop_products','product_id','=','safety_shop_products.id')->where('safety_shop_products.company_id',$companyId)->sum(DB::raw('quantity * cost_cents')),
                'low_stock'=>Product::where('company_id',$companyId)->where('is_active',true)->whereRaw('(SELECT COALESCE(SUM(quantity),0) FROM safety_shop_stocks WHERE product_id = safety_shop_products.id) <= reorder_level')->count(),
                'movements'=>Movement::where('company_id',$companyId)->whereBetween('movement_date',[$monthStart,$monthEnd])->count(),
            ];
        }
        $rentalMonthly=$this->scopedRentalMonthly($request,$month);
        $combinedRevenue=['manpower_revenue'=>(int)$rentalMonthly->sum('po_revenue_cents'),'manpower_cost'=>(int)$rentalMonthly->sum('employee_cost_cents'),'company_cost'=>(int)$rentalMonthly->sum('company_cost_cents'),'shop_revenue'=>(int)($safetyShopAnalytics['net_revenue']??0),'shop_profit'=>(int)($safetyShopAnalytics['gross_profit']??0)];
        $combinedRevenue['revenue']=$combinedRevenue['manpower_revenue']+$combinedRevenue['shop_revenue'];$combinedRevenue['profit']=($combinedRevenue['manpower_revenue']-$combinedRevenue['manpower_cost']-$combinedRevenue['company_cost'])+$combinedRevenue['shop_profit'];
        return view('dashboard', compact('month', 'stats', 'previous', 'changes', 'recent', 'pending', 'companies', 'companyAnalytics', 'trend', 'maxHours', 'maxPayroll', 'invoiceStats','safetyShopAnalytics','safetyShopStockAnalytics','combinedRevenue'));
    }
    private function scopedRentalMonthly(Request $request,string $month){$q=Payroll::where('month',$month)->where('employment_type','rental');if(!$request->user()->isSuperAdmin())$q->where('company_id',$request->user()->company_id);if($request->user()->isManager())$q->where('branch_id',$request->user()->branch_id);return $q;}
}
