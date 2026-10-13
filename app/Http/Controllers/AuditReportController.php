<?php
namespace App\Http\Controllers;
use App\Models\{ActivityLog, Company, Employee, Payroll, Timesheet, User};
use App\Services\Documents;
use App\Services\Access;
use Illuminate\Http\Request;
use App\Modules\SafetyShop\Sales\Models\{Sale,SaleReturn};
use App\Modules\SafetyShop\Stock\Models\Movement;
use App\Modules\SafetyShop\Shared\Models\Master;

class AuditReportController extends Controller
{
    private function filters(Request $request) {
        $filters = $request->validate([
            'report' => 'nullable|in:activity,employees,timesheets,salaries,shop_sales,shop_returns,stock_movements', 'from' => 'nullable|date_format:Y-m-d',
            'to' => 'nullable|date_format:Y-m-d|after_or_equal:from', 'employment_type' => 'nullable|in:rental,own',
            'company_id' => 'nullable|integer|exists:companies,id', 'employee_id' => 'nullable|integer|exists:employees,id',
            'status' => 'nullable|string|max:30', 'user_id' => 'nullable|integer|exists:users,id',
            'action' => 'nullable|string|max:255', 'search' => 'nullable|string|max:100',
            'location_id' => 'nullable|integer|exists:safety_shop_masters,id',
        ]);
        $filters['report'] = $filters['report'] ?? 'activity';
        $filters['from'] = $filters['from'] ?? now()->startOfMonth()->format('Y-m-d');
        $filters['to'] = $filters['to'] ?? now()->format('Y-m-d');
        return $filters;
    }
    private function report(Request $request) {
        $filters = $this->filters($request);
        if (!config('manpower.enabled') && in_array($filters['report'], ['employees', 'timesheets', 'salaries'], true)) abort(404);
        if (!config('safety_shop.enabled') && in_array($filters['report'], ['shop_sales', 'shop_returns', 'stock_movements'], true)) abort(404);
        if(in_array($filters['report'],['shop_sales','shop_returns','stock_movements'],true)) abort_unless(auth()->user()->isCompanyAdmin(),403);
        if ($filters['report'] === 'employees') {
            $query = Access::employees()->with(['company', 'designation'])->whereBetween('joined_on', [$filters['from'], $filters['to']]);
            $this->employeeFilters($query, $filters);
            if (!empty($filters['search'])) $query->where(function ($q) use ($filters) { $q->where('name', 'like', '%'.$filters['search'].'%')->orWhere('iqama_number', 'like', '%'.$filters['search'].'%')->orWhere('passport_number', 'like', '%'.$filters['search'].'%'); });
            return [$filters, $query];
        }
        if ($filters['report'] === 'timesheets') {
            $query = Timesheet::with(['employee.company', 'employee.designation', 'creator', 'reviewer'])->whereIn('employee_id', Access::employees()->select('id'))->whereBetween('work_date', [$filters['from'], $filters['to']]);
            if(!auth()->user()->isSuperAdmin())$query->where('company_id',auth()->user()->company_id); if(auth()->user()->isBranchUser())$query->where('branch_id',auth()->user()->branch_id);
            $this->relatedEmployeeFilters($query, $filters);
            if (!empty($filters['status'])) $query->where('status', $filters['status']);
            if (!empty($filters['search'])) $query->whereHas('employee', function ($q) use ($filters) { $q->where('name', 'like', '%'.$filters['search'].'%')->orWhere('iqama_number', 'like', '%'.$filters['search'].'%'); });
            return [$filters, $query];
        }
        if ($filters['report'] === 'salaries') {
            $query = Payroll::with(['employee', 'approver'])->whereIn('employee_id',Access::employees()->select('id'))->whereBetween('month', [substr($filters['from'], 0, 7), substr($filters['to'], 0, 7)]);
            if (!empty($filters['employment_type'])) $query->where('employment_type', $filters['employment_type']);
            if (!empty($filters['company_id'])) $query->where('company_id', $filters['company_id']);
            if (!empty($filters['employee_id'])) $query->where('employee_id', $filters['employee_id']);
            if (!empty($filters['status'])) $query->where('status', $filters['status']);
            if (!empty($filters['search'])) $query->where(function ($q) use ($filters) { $q->where('employee_name', 'like', '%'.$filters['search'].'%')->orWhere('iqama_number', 'like', '%'.$filters['search'].'%')->orWhere('company_name', 'like', '%'.$filters['search'].'%'); });
            return [$filters, $query];
        }
        if ($filters['report'] === 'shop_sales') {
            $companyId=(int)auth()->user()->company_id;
            $query=Sale::with(['location','creator'])->whereHas('location',fn($q)=>$q->where('company_id',$companyId))->whereBetween('created_at',[$filters['from'].' 00:00:00',$filters['to'].' 23:59:59']);
            if(!empty($filters['location_id']))$query->where('location_id',$filters['location_id']);
            if(!empty($filters['user_id']))$query->where('created_by',$filters['user_id']);
            if(!empty($filters['search'])){$search=$filters['search'];$query->where(function($q)use($search){$q->where('id',$search)->orWhere('customer','like','%'.$search.'%')->orWhere('customer_phone','like','%'.$search.'%')->orWhere('payment_method','like','%'.$search.'%');});}
            return [$filters,$query];
        }
        if ($filters['report'] === 'shop_returns') {
            $companyId=(int)auth()->user()->company_id;
            $query=SaleReturn::with(['sale','location','creator'])->whereHas('location',fn($q)=>$q->where('company_id',$companyId))->whereBetween('created_at',[$filters['from'].' 00:00:00',$filters['to'].' 23:59:59']);
            if(!empty($filters['location_id']))$query->where('location_id',$filters['location_id']);
            if(!empty($filters['user_id']))$query->where('created_by',$filters['user_id']);
            if(!empty($filters['search'])){$search=$filters['search'];$query->where(function($q)use($search){$q->where('id',$search)->orWhere('sale_id',$search)->orWhere('reason','like','%'.$search.'%')->orWhere('refund_method','like','%'.$search.'%');});}
            return [$filters,$query];
        }
        if ($filters['report'] === 'stock_movements') {
            $query=Movement::with(['product','location','destination','supplier','creator'])->where('company_id',auth()->user()->company_id)->whereBetween('movement_date',[$filters['from'],$filters['to']]);
            if(!empty($filters['location_id']))$query->where(fn($q)=>$q->where('location_id',$filters['location_id'])->orWhere('destination_id',$filters['location_id']));
            if(!empty($filters['user_id']))$query->where('created_by',$filters['user_id']);
            if(!empty($filters['action']))$query->where('type',$filters['action']);
            if(!empty($filters['search'])){$search=$filters['search'];$query->where(function($q)use($search){$q->where('reference','like','%'.$search.'%')->orWhere('recipient','like','%'.$search.'%')->orWhere('notes','like','%'.$search.'%')->orWhereHas('product',fn($p)=>$p->where('sku','like','%'.$search.'%')->orWhere('name','like','%'.$search.'%'));});}
            return [$filters,$query];
        }
        $query = ActivityLog::with('user')->whereBetween('created_at', [$filters['from'].' 00:00:00', $filters['to'].' 23:59:59']);
        $userIds=User::where('company_id',auth()->user()->company_id)->when(auth()->user()->isBranchUser(),fn($q)=>$q->where('branch_id',auth()->user()->branch_id))->pluck('id')->push(auth()->id())->unique();
        $query->whereIn('user_id',$userIds);
        if (!empty($filters['user_id'])) $query->where('user_id', $filters['user_id']);
        if (!empty($filters['action'])) $query->where('action', $filters['action']);
        if (!empty($filters['search'])) $query->where('subject', 'like', '%'.$filters['search'].'%');
        return [$filters, $query];
    }
    private function employeeFilters($query, array $filters) {
        if (!empty($filters['employment_type'])) $query->where('employment_type', $filters['employment_type']);
        if (!empty($filters['company_id'])) $query->where('company_id', $filters['company_id']);
        if (!empty($filters['employee_id'])) $query->where('id', $filters['employee_id']);
        if (!empty($filters['status'])) $query->where('status', $filters['status']);
    }
    private function relatedEmployeeFilters($query, array $filters) {
        if (!empty($filters['employee_id'])) $query->where('employee_id', $filters['employee_id']);
        if (!empty($filters['employment_type']) || !empty($filters['company_id'])) $query->whereHas('employee', function ($q) use ($filters) {
            if (!empty($filters['employment_type'])) $q->where('employment_type', $filters['employment_type']);
            if (!empty($filters['company_id'])) $q->where('company_id', $filters['company_id']);
        });
    }
    private function headings($type) { return [
        'activity' => ['Date / time', 'Actor', 'Username', 'Event', 'Record / details'],
        'employees' => ['Employee', 'Type', 'Company', 'Designation', 'Iqama', 'Joined', 'Status'],
        'timesheets' => ['Date', 'Employee', 'Type', 'Company', 'Regular hours', 'Overtime', 'Status', 'Reviewed by'],
        'salaries' => ['Month', 'Employee', 'Type', 'Company', 'Regular pay', 'Overtime pay', 'Allowances', 'Deductions', 'Net pay', 'Status'],
        'shop_sales' => ['Date / time','Receipt','Customer','Location','Subtotal SAR','Discount SAR','VAT SAR','Total SAR','Payment','Posted by'],
        'shop_returns' => ['Date / time','Return','Receipt','Customer','Location','Refund SAR','Method','Reason','Posted by'],
        'stock_movements' => ['Date','Movement','Type','SKU','Product','Location','Destination','Quantity','Balance','Reference','Supplier / recipient','Posted by'],
    ][$type]; }
    private function row($r, $type) {
        if ($type === 'employees') return [$r->name, ucfirst($r->employment_type), optional($r->company)->name, optional($r->designation)->name, $r->iqama_number, $r->joined_on->format('Y-m-d'), ucfirst($r->status)];
        if ($type === 'timesheets') return [$r->work_date->format('Y-m-d').' ('.$r->work_date->format('D').')', optional($r->employee)->name, ucfirst(optional($r->employee)->employment_type), optional(optional($r->employee)->company)->name, number_format($r->regular_units / 100, 2), number_format($r->overtime_units / 100, 2), ucfirst($r->status), optional($r->reviewer)->name ?: '—'];
        if ($type === 'salaries') {
            $allowances = $r->allowance_cents + $r->meal_allowance_cents + $r->transportation_allowance_cents + $r->housing_allowance_cents + $r->medical_allowance_cents;
            $deductions = $r->deduction_cents + $r->advance_deduction_cents + $r->retirement_insurance_cents + $r->tax_cents;
            return [$r->month, $r->employee_name, ucfirst($r->employment_type), $r->company_name, number_format($r->regular_pay_cents / 100, 2), number_format($r->overtime_pay_cents / 100, 2), number_format($allowances / 100, 2), number_format($deductions / 100, 2), number_format($r->net_pay_cents / 100, 2), ucfirst($r->status)];
        }
        if($type==='shop_sales')return [$r->created_at->format('Y-m-d H:i:s'),'SALE-'.$r->id,$r->customer,optional($r->location)->name,number_format($r->subtotal_cents/100,2),number_format($r->discount_cents/100,2),number_format($r->tax_cents/100,2),number_format($r->total_cents/100,2),ucfirst($r->payment_method),optional($r->creator)->name];
        if($type==='shop_returns')return [$r->created_at->format('Y-m-d H:i:s'),'RETURN-'.$r->id,'SALE-'.$r->sale_id,optional($r->sale)->customer,optional($r->location)->name,number_format($r->refund_cents/100,2),ucfirst(str_replace('_',' ',$r->refund_method)),$r->reason,optional($r->creator)->name];
        if($type==='stock_movements')return [$r->movement_date->format('Y-m-d'),'MOV-'.$r->id,ucfirst($r->type),optional($r->product)->sku,optional($r->product)->name,optional($r->location)->name,optional($r->destination)->name,$r->quantity,$r->balance_after,$r->reference,optional($r->supplier)->name ?: $r->recipient,optional($r->creator)->name];
        return [$r->created_at->format('Y-m-d H:i:s'), optional($r->user)->name ?: 'System', optional($r->user)->username ?: 'system', $r->action, $r->subject];
    }
    private function title($type) { return ['activity' => 'Activity Trail', 'employees' => 'Employee Details', 'timesheets' => 'Timesheet & Attendance Details', 'salaries' => 'Salary Details','shop_sales'=>'Safety Shop Sales','shop_returns'=>'Safety Shop Returns','stock_movements'=>'Safety Shop Stock Movements'][$type]; }
    private function orderColumn($type) { return $type === 'timesheets' ? 'work_date' : ($type === 'salaries' ? 'month' : ($type==='stock_movements'?'movement_date':'created_at')); }
    private function summary($query, $type) {
        $count = (clone $query)->count();
        if ($type === 'timesheets') {
            $totals = (clone $query)->selectRaw('COALESCE(SUM(regular_units),0) as regular, COALESCE(SUM(overtime_units),0) as overtime')->first();
            return ['Records' => number_format($count), 'Regular hours' => number_format($totals->regular / 100, 2), 'Overtime hours' => number_format($totals->overtime / 100, 2)];
        }
        if ($type === 'salaries') {
            $totals = (clone $query)->selectRaw('COALESCE(SUM(regular_pay_cents),0) as regular, COALESCE(SUM(overtime_pay_cents),0) as overtime, COALESCE(SUM(allowance_cents + meal_allowance_cents + transportation_allowance_cents + housing_allowance_cents + medical_allowance_cents),0) as allowances, COALESCE(SUM(deduction_cents + advance_deduction_cents + retirement_insurance_cents + tax_cents),0) as deductions, COALESCE(SUM(net_pay_cents),0) as net')->first();
            return ['Salary records' => number_format($count), 'Regular pay · SAR' => number_format($totals->regular / 100, 2), 'Overtime pay · SAR' => number_format($totals->overtime / 100, 2), 'Allowances · SAR' => number_format($totals->allowances / 100, 2), 'Deductions · SAR' => number_format($totals->deductions / 100, 2), 'Net pay · SAR' => number_format($totals->net / 100, 2)];
        }
        if($type==='shop_sales'){$total=(int)(clone $query)->sum('total_cents');return ['Sales receipts'=>number_format($count),'Total collected · SAR'=>number_format($total/100,2)];}
        if($type==='shop_returns'){$total=(int)(clone $query)->sum('refund_cents');return ['Returns'=>number_format($count),'Refunded · SAR'=>number_format($total/100,2)];}
        if($type==='stock_movements')return ['Movements'=>number_format($count),'Net quantity change'=>number_format((int)(clone $query)->sum('quantity'))];
        return [$type === 'employees' ? 'Total employees' : 'Total activities' => number_format($count)];
    }
    public function index(Request $request) {
        [$filters, $query] = $this->report($request); $total = (clone $query)->count();
        $summary = $this->summary($query, $filters['report']);
        $records = $query->latest($this->orderColumn($filters['report']))->paginate(30)->withQueryString();
        $rows = $records->map(fn ($record) => $this->row($record, $filters['report']));
        $users = User::where('company_id',auth()->user()->company_id)->when(auth()->user()->isBranchUser(),fn($q)=>$q->where('branch_id',auth()->user()->branch_id))->orderBy('name')->get();
        $actions = $filters['report']==='stock_movements' ? collect(['receipt','issue','return','adjustment','transfer']) : ActivityLog::whereIn('user_id',$users->pluck('id')->push(auth()->id())->unique())->distinct()->orderBy('action')->pluck('action');
        $companies = Access::companies()->orderBy('name')->get();
        $locations = auth()->user()->isCompanyAdmin()?Master::where('company_id',auth()->user()->company_id)->where('type','location')->orderBy('name')->get():collect();
        $employees = !empty($filters['employee_id']) ? Access::employees()->where('id', $filters['employee_id'])->get() : collect();
        $headings = $this->headings($filters['report']); $reportTitle = $this->title($filters['report']);
        return view('audit.index', compact('filters', 'records', 'rows', 'users', 'actions', 'companies', 'employees', 'locations', 'headings', 'reportTitle', 'total', 'summary'));
    }
    public function csv(Request $request) {
        [$filters, $query] = $this->report($request); $summary = $this->summary($query, $filters['report']); $records = $query->latest($this->orderColumn($filters['report']))->get(); $headings = $this->headings($filters['report']);
        return response()->streamDownload(function () use ($records, $headings, $filters, $summary) {
            $file = fopen('php://output', 'w'); fputcsv($file, $headings);
            foreach ($records as $record) fputcsv($file, array_map(function ($value) { return preg_match('/^[=+\-@\t\r\n]/', (string) $value) ? "'".$value : $value; }, $this->row($record, $filters['report'])));
            fputcsv($file, []); fputcsv($file, ['REPORT TOTALS']); foreach ($summary as $label => $value) fputcsv($file, [$label, $value]);
            fclose($file);
        }, 'company-audit-'.$filters['report'].'-'.$filters['from'].'-to-'.$filters['to'].'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
    public function pdf(Request $request) {
        [$filters, $query] = $this->report($request); $summary = $this->summary($query, $filters['report']); $records = $query->latest($this->orderColumn($filters['report']))->get();
        $rows = $records->map(fn ($record) => $this->row($record, $filters['report'])); $headings = $this->headings($filters['report']); $reportTitle = $this->title($filters['report']);
        return Documents::download(Documents::render('pdf.audit', compact('rows', 'headings', 'reportTitle', 'filters', 'summary')), 'company-audit-'.$filters['report'].'-'.$filters['from'].'-to-'.$filters['to'].'.pdf');
    }
}
