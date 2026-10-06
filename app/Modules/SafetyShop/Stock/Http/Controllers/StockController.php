<?php
namespace App\Modules\SafetyShop\Stock\Http\Controllers;
use App\Http\Controllers\Controller;
use App\Modules\SafetyShop\Shared\Models\Master;
use App\Modules\SafetyShop\Products\Models\Product;
use App\Modules\SafetyShop\Stock\Models\Stock;
use App\Modules\SafetyShop\Stock\Models\Movement;
use App\Modules\SafetyShop\Stock\Services\InventoryService;
use App\Modules\SafetyShop\Stock\Services\LedgerQuery;
use App\Modules\SafetyShop\Sales\Models\Sale;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
class StockController extends Controller
{
    private function movementsQuery(Request $r) { return (new LedgerQuery)->build($r); }
    public function create()
    {
        $products=Product::where('is_active',true)->orderBy('name')->get(); $locations=Master::where('type','location')->where('is_active',true)->orderBy('name')->get(); $suppliers=Master::where('type','supplier')->where('is_active',true)->orderBy('name')->get(); $requestKey=(string)Str::uuid();
        $recipients=Sale::query()->whereNotNull('customer')->where('customer','<>','')->distinct()->pluck('customer')
            ->merge(Movement::query()->whereNotNull('recipient')->where('recipient','<>','')->distinct()->pluck('recipient'))
            ->map(fn ($recipient) => trim($recipient))->filter()->unique(fn ($recipient) => mb_strtolower($recipient))->sort()->values();
        return view('safety-shop.stock.form',compact('products','locations','suppliers','recipients','requestKey'));
    }
    public function store(Request $r, InventoryService $service)
    {
        $data=$r->validate(['request_key'=>'required|uuid','type'=>'required|in:receipt,issue,return,adjustment,transfer','product_id'=>'required|integer|exists:safety_shop_products,id','location_id'=>'required|integer','destination_id'=>'nullable|required_if:type,transfer|integer','supplier_id'=>'nullable|integer','quantity'=>'required|integer|between:-1000000,1000000','movement_date'=>'required|date_format:Y-m-d|before_or_equal:today','reference'=>'nullable|string|max:100','recipient'=>'nullable|required_if:type,issue|string|max:150','notes'=>'required|string|max:2000']);
        $movement=$service->post($data,$r->user()->id);
        return redirect()->route('safety-shop.products.show',$movement->product_id)->with('success','Stock movement #'.$movement->id.' posted.');
    }
    public function index(Request $r) { $movements=$this->movementsQuery($r)->latest('id')->paginate(25)->withQueryString(); $products=Product::orderBy('name')->get(); $locations=Master::where('type','location')->orderBy('name')->get(); return view('safety-shop.stock.index',compact('movements','products','locations')); }

}
