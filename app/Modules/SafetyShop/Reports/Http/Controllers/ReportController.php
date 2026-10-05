<?php
namespace App\Modules\SafetyShop\Reports\Http\Controllers;
use App\Http\Controllers\Controller;
use App\Modules\SafetyShop\Products\Models\Product;
use App\Modules\SafetyShop\Stock\Models\Stock;
use App\Modules\SafetyShop\Products\Services\CatalogQuery;
use App\Modules\SafetyShop\Stock\Services\LedgerQuery;
use Illuminate\Http\Request;
class ReportController extends Controller
{
    public function index() { return view('safety-shop.reports.index'); }
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

}
