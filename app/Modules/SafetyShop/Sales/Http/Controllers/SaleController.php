<?php
namespace App\Modules\SafetyShop\Sales\Http\Controllers;
use App\Http\Controllers\Controller;
use App\Modules\SafetyShop\Shared\Models\Master;
use App\Modules\SafetyShop\Products\Models\Product;
use App\Modules\SafetyShop\Stock\Models\Stock;
use App\Modules\SafetyShop\Sales\Models\Sale;
use App\Modules\SafetyShop\Sales\Models\Customer;
use App\Modules\SafetyShop\Sales\Services\SaleService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use App\Modules\SafetyShop\Shared\Models\ReceiptSetting;
class SaleController extends Controller
{
    public function index() { $sales=Sale::with(['location','creator'])->latest('id')->paginate(25); return view('safety-shop.sales.index',compact('sales')); }
    public function create() { $locations=Master::where('type','location')->where('is_active',true)->orderBy('name')->get(); $customers=Customer::where('is_active',true)->orderBy('name')->orderBy('phone')->get(); $requestKey=(string)Str::uuid(); return view('safety-shop.sales.checkout',compact('locations','customers','requestKey')); }
    public function barcode(Request $r)
    {
        $data=$r->validate(['barcode'=>'required|string|max:100','location_id'=>'required|integer']);
        abort_unless(Master::whereKey($data['location_id'])->where('type','location')->where('is_active',true)->exists(),422);
        $product=Product::where('barcode',$data['barcode'])->where('is_active',true)->firstOrFail();
        $stock=Stock::where('product_id',$product->id)->where('location_id',$data['location_id'])->value('quantity')??0;
        return response()->json(['id'=>$product->id,'sku'=>$product->sku,'name'=>$product->name,'barcode'=>$product->barcode,'unit'=>$product->unit,'price_cents'=>$product->price_cents,'available'=>(int)$stock]);
    }
    public function store(Request $r, SaleService $service)
    {
        $money='nullable|regex:/^\d{1,8}(\.\d{1,2})?$/';
        $data=$r->validate(['request_key'=>'required|uuid','location_id'=>'required|integer','customer_id'=>'nullable|integer|exists:safety_shop_customers,id','customer'=>'nullable|string|max:150','customer_phone'=>['nullable','string','max:30','regex:/^[0-9+() .-]+$/'],'customer_email'=>'nullable|email|max:150','customer_address'=>'nullable|string|max:500','discount'=>$money,'tax_rate'=>'nullable|numeric|min:0|max:100','cash_paid'=>$money,'card_paid'=>$money,'bank_paid'=>$money,'payment_method'=>'nullable|in:cash,card,bank','paid'=>$money,'lines'=>'required|array|min:1|max:100','lines.*.product_id'=>'required|integer|distinct|exists:safety_shop_products,id','lines.*.quantity'=>'required|integer|min:1|max:1000000','lines.*.price_cents'=>'required|integer|min:0|max:9999999999']);
        $data['customer']=$data['customer']??'Walk-in customer';
        $sale=$service->post($data,$r->user()->id);
        return redirect()->route('safety-shop.sales.show',$sale)->with('success','Sale posted and stock updated.');
    }
    public function show(Sale $sale) { $sale->load(['lines','location','creator']); $receiptSettings=ReceiptSetting::firstOrFail(); $receiptLogo=null; if($receiptSettings->logo_path&&Storage::disk('local')->exists($receiptSettings->logo_path)){$path=Storage::disk('local')->path($receiptSettings->logo_path);$receiptLogo='data:'.mime_content_type($path).';base64,'.base64_encode(file_get_contents($path));} return view('safety-shop.sales.receipt',compact('sale','receiptSettings','receiptLogo')); }
}
