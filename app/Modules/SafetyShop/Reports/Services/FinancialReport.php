<?php
namespace App\Modules\SafetyShop\Reports\Services;

use App\Modules\SafetyShop\Sales\Models\Sale;
use App\Modules\SafetyShop\Sales\Models\SaleReturn;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class FinancialReport
{
    public function period(array $filters): array
    {
        $from=Carbon::parse($filters['from']??now()->startOfMonth()->toDateString())->startOfDay();
        $to=Carbon::parse($filters['to']??now()->toDateString())->endOfDay();
        return [$from,$to];
    }

    public function build(array $filters): array
    {
        [$from,$to]=$this->period($filters);
        $sales=Sale::whereBetween('created_at',[$from,$to]);
        $returns=SaleReturn::whereBetween('created_at',[$from,$to]);
        $saleTotals=(clone $sales)->selectRaw('COUNT(*) count, COALESCE(SUM(subtotal_cents),0) gross, COALESCE(SUM(discount_cents),0) discounts, COALESCE(SUM(taxable_cents),0) taxable, COALESCE(SUM(tax_cents),0) tax, COALESCE(SUM(total_cents),0) billed')->first();
        $returnTotals=(clone $returns)->selectRaw('COUNT(*) count, COALESCE(SUM(refund_cents),0) refunds, COALESCE(SUM(tax_refund_cents),0) tax_refunds, COALESCE(SUM(cost_reversal_cents),0) returned_cost')->first();
        $cogs=(int)DB::table('safety_shop_sale_lines as lines')->join('safety_shop_sales as sales','sales.id','=','lines.sale_id')->whereBetween('sales.created_at',[$from,$to])->sum(DB::raw('lines.quantity * lines.cost_cents'));
        $netRevenue=(int)$saleTotals->taxable-((int)$returnTotals->refunds-(int)$returnTotals->tax_refunds);
        $netCogs=$cogs-(int)$returnTotals->returned_cost;
        $profit=$netRevenue-$netCogs;
        $summary=[
            'sales_count'=>(int)$saleTotals->count,'return_count'=>(int)$returnTotals->count,
            'gross_sales'=>(int)$saleTotals->gross,'discounts'=>(int)$saleTotals->discounts,
            'vat_collected'=>(int)$saleTotals->tax-(int)$returnTotals->tax_refunds,
            'refunds'=>(int)$returnTotals->refunds,'net_revenue'=>$netRevenue,'net_cogs'=>$netCogs,
            'gross_profit'=>$profit,'loss'=>max(0,-$profit),
            'margin'=>$netRevenue>0?round($profit/$netRevenue*100,2):0,
        ];
        $events=$this->events($from,$to,50);
        $trend=$this->trend($from,$to);
        return compact('from','to','summary','events','trend');
    }

    public function events($from,$to,$limit=null)
    {
        $sales=Sale::with(['creator','location','lines'])->whereBetween('created_at',[$from,$to])->get()->map(function($sale){
            $cost=$sale->lines->sum(fn($line)=>$line->quantity*$line->cost_cents);
            return (object)['date'=>$sale->created_at,'type'=>'Sale','number'=>'SALE-'.$sale->id,'reference'=>'—','customer'=>$sale->customer,'location'=>$sale->location->name,'gross'=>$sale->subtotal_cents,'discount'=>$sale->discount_cents,'vat'=>$sale->tax_cents,'refund'=>0,'revenue'=>$sale->taxable_cents,'cost'=>$cost,'profit'=>$sale->taxable_cents-$cost,'method'=>$sale->payment_method,'user'=>$sale->creator->name];
        });
        $returns=SaleReturn::with(['sale','creator','location'])->whereBetween('created_at',[$from,$to])->get()->map(function($return){
            $revenue=-($return->refund_cents-$return->tax_refund_cents); $profit=$revenue+$return->cost_reversal_cents;
            return (object)['date'=>$return->created_at,'type'=>'Return','number'=>'RETURN-'.$return->id,'reference'=>'SALE-'.$return->sale_id,'customer'=>$return->sale->customer,'location'=>$return->location->name,'gross'=>-$return->gross_refund_cents,'discount'=>-$return->discount_refund_cents,'vat'=>-$return->tax_refund_cents,'refund'=>$return->refund_cents,'revenue'=>$revenue,'cost'=>-$return->cost_reversal_cents,'profit'=>$profit,'method'=>$return->refund_method,'user'=>$return->creator->name];
        });
        $events=$sales->concat($returns)->sortByDesc('date')->values();
        return $limit?$events->take($limit):$events;
    }

    private function trend($from,$to)
    {
        $sales=Sale::whereBetween('created_at',[$from,$to])->selectRaw('DATE(created_at) day, SUM(taxable_cents) revenue')->groupBy(DB::raw('DATE(created_at)'))->pluck('revenue','day');
        $returns=SaleReturn::whereBetween('created_at',[$from,$to])->selectRaw('DATE(created_at) day, SUM(refund_cents-tax_refund_cents) refunds')->groupBy(DB::raw('DATE(created_at)'))->pluck('refunds','day');
        return collect($sales->keys())->merge($returns->keys())->unique()->sort()->map(function($day) use($sales,$returns){ return ['day'=>$day,'revenue'=>(int)($sales[$day]??0),'returns'=>(int)($returns[$day]??0),'net'=>(int)($sales[$day]??0)-(int)($returns[$day]??0)]; })->values();
    }
}
