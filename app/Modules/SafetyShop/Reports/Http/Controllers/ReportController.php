<?php
namespace App\Modules\SafetyShop\Reports\Http\Controllers;
use App\Http\Controllers\Controller;
use App\Modules\SafetyShop\Products\Models\Product;
use App\Modules\SafetyShop\Stock\Models\Stock;
use App\Modules\SafetyShop\Products\Services\CatalogQuery;
use App\Modules\SafetyShop\Stock\Services\LedgerQuery;
use App\Modules\SafetyShop\Reports\Services\FinancialReport;
use App\Services\Documents;
use Illuminate\Http\Request;
class ReportController extends Controller
{
    public function index(Request $r, FinancialReport $financial)
    {
        $filters=$r->validate(['from'=>'nullable|date','to'=>'nullable|date|after_or_equal:from']);
        $report=$financial->build($filters);
        return view('safety-shop.reports.index',$report);
    }
    private function productsQuery(Request $r) { return (new CatalogQuery)->build($r); }
    private function movementsQuery(Request $r) { return (new LedgerQuery)->build($r); }
    public function export(Request $r)
    {
        $ledger=$r->get('report')==='movements'; $query=$ledger?$this->movementsQuery($r)->orderBy('id'):$this->productsQuery($r)->orderBy('id');
        return response()->streamDownload(function () use ($query,$ledger) {
            $out=fopen('php://output','w'); fwrite($out,"\xEF\xBB\xBF");
            fputcsv($out,$ledger?['ID','Date','Type','SKU','Product','Location','Destination','Quantity change','Source balance','Destination balance','Supplier','Recipient','Reference','Notes','Posted by']:['SKU','Barcode','Product','Category','Unit','Brand','Size','Standard','Stock','Reorder level','Cost SAR','Price SAR','Status']);
            foreach ($query->lazy(200) as $row) {
                $values=$ledger?[$row->id,$row->movement_date->format('Y-m-d'),$row->type,$row->product->sku,$row->product->name,$row->location->name,optional($row->destination)->name,$row->quantity,$row->balance_after,$row->destination_balance_after,optional($row->supplier)->name,$row->recipient,$row->reference,$row->notes,$row->creator->name]:[$row->sku,$row->barcode,$row->name,optional($row->category)->name,$row->unit,$row->brand,$row->size,$row->safety_standard,$row->stock_total,$row->reorder_level,number_format($row->cost_cents/100,2,'.',''),number_format($row->price_cents/100,2,'.',''),$row->is_active?'Active':'Inactive'];
                fputcsv($out,array_map(function ($value) { return is_string($value)&&preg_match('/^[=+\-@\t\r\n]/',$value)?"'".$value:$value; },$values));
            }
            fclose($out);
        },$ledger?'safety-shop-movements.csv':'safety-shop-stock.csv',['Content-Type'=>'text/csv; charset=UTF-8']);
    }

    public function financialCsv(Request $r, FinancialReport $financial)
    {
        $filters=$r->validate(['from'=>'nullable|date','to'=>'nullable|date|after_or_equal:from']); [$from,$to]=$financial->period($filters); $events=$financial->events($from,$to);
        return response()->streamDownload(function() use($events){ $out=fopen('php://output','w'); fwrite($out,"\xEF\xBB\xBF"); fputcsv($out,['Date','Type','Number','Original receipt','Customer','Location','Gross SAR','Discount SAR','VAT SAR','Refund SAR','Net revenue SAR','Cost SAR','Profit/Loss SAR','Method','User']); foreach($events as $e){$values=[$e->date->format('Y-m-d H:i'),$e->type,$e->number,$e->reference,$e->customer,$e->location,$e->gross/100,$e->discount/100,$e->vat/100,$e->refund/100,$e->revenue/100,$e->cost/100,$e->profit/100,$e->method,$e->user];fputcsv($out,array_map(fn($v)=>is_string($v)&&preg_match('/^[=+\-@\t\r\n]/',$v)?"'".$v:$v,$values));} fclose($out); },'safety-shop-financial-audit-'.$from->format('Ymd').'-'.$to->format('Ymd').'.csv',['Content-Type'=>'text/csv; charset=UTF-8']);
    }

    public function financialPdf(Request $r, FinancialReport $financial)
    {
        $filters=$r->validate(['from'=>'nullable|date','to'=>'nullable|date|after_or_equal:from']); $report=$financial->build($filters); $report['events']=$financial->events($report['from'],$report['to']);
        return Documents::download(Documents::render('pdf.safety-shop-financial-audit',$report,'A4','landscape'),'safety-shop-financial-audit-'.$report['from']->format('Ymd').'-'.$report['to']->format('Ymd').'.pdf');
    }

}
