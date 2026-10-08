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
use App\Services\Documents;
class SaleController extends Controller
{
    public function index() { $sales=Sale::with(['location','creator'])->latest('id')->paginate(25); return view('safety-shop.sales.index',compact('sales')); }
    public function create() { $settings=ReceiptSetting::first(); $shopLocation=Master::where('type','location')->where('is_active',true)->when(optional($settings)->default_location_id,fn($query,$id)=>$query->whereKey($id))->first(); if(!$shopLocation)$shopLocation=Master::where('type','location')->where('is_active',true)->orderBy('name')->first(); $customers=Customer::where('is_active',true)->where('approval_status','approved')->orderBy('customer_type')->orderBy('name')->orderBy('phone')->get(); $requestKey=(string)Str::uuid(); return view('safety-shop.sales.checkout',compact('shopLocation','customers','requestKey')); }
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
        if($r->filled('customer_id')){
            $customer=Customer::whereKey($r->customer_id)->where('is_active',true)->firstOrFail();
            $r->merge(['customer_type'=>$customer->customer_type,'customer'=>$r->filled('customer')?$r->customer:$customer->name,'customer_contact_person'=>$r->filled('customer_contact_person')?$r->customer_contact_person:$customer->contact_person,'customer_phone'=>$r->filled('customer_phone')?$r->customer_phone:$customer->phone,'customer_email'=>$r->filled('customer_email')?$r->customer_email:$customer->email,'customer_vat_number'=>$r->filled('customer_vat_number')?$r->customer_vat_number:$customer->vat_number,'customer_commercial_registration'=>$r->filled('customer_commercial_registration')?$r->customer_commercial_registration:$customer->commercial_registration,'customer_address'=>$r->filled('customer_address')?$r->customer_address:$customer->address,'customer_building_number'=>$customer->building_number,'customer_street'=>$customer->street,'customer_district'=>$customer->district,'customer_city'=>$customer->city,'customer_postal_code'=>$customer->postal_code,'customer_country_code'=>$customer->country_code?:'SA']);
        }
        $r->merge(['customer_type'=>$r->input('customer_type','retail')]);
        $money='nullable|regex:/^\d{1,8}(\.\d{1,2})?$/';
        $data=$r->validate(['request_key'=>'required|uuid','location_id'=>'required|integer','customer_id'=>'nullable|integer|exists:safety_shop_customers,id','customer_type'=>'required|in:retail,company','customer'=>'nullable|string|max:150','customer_contact_person'=>'nullable|string|max:150','customer_phone'=>['nullable','string','max:30','regex:/^[0-9+() .-]+$/'],'customer_email'=>'nullable|email|max:150','customer_vat_number'=>'nullable|digits:15','customer_commercial_registration'=>'nullable|string|max:30','customer_address'=>'nullable|string|max:500','customer_building_number'=>'nullable|string|max:20','customer_street'=>'nullable|string|max:150','customer_district'=>'nullable|string|max:100','customer_city'=>'nullable|string|max:100','customer_postal_code'=>'nullable|string|max:10','customer_country_code'=>'nullable|string|size:2','discount'=>$money,'tax_rate'=>'nullable|numeric|min:0|max:100','cash_paid'=>$money,'card_paid'=>$money,'bank_paid'=>$money,'payment_method'=>'nullable|in:cash,card,bank','paid'=>$money,'lines'=>'required|array|min:1|max:100','lines.*.product_id'=>'required|integer|distinct|exists:safety_shop_products,id','lines.*.quantity'=>'required|integer|min:1|max:1000000','lines.*.price_cents'=>'required|integer|min:0|max:9999999999']);
        if($data['customer_type']==='company' && empty($data['customer_id'])) throw \Illuminate\Validation\ValidationException::withMessages(['customer_id'=>'Create and approve the company purchaser in Customer Master before selecting it in checkout.']);
        $data['customer']=$data['customer']??'Walk-in customer';
        $sale=$service->post($data,$r->user()->id);
        $r->session()->put('safety_shop_location_id',(int)$data['location_id']);
        return redirect()->route('safety-shop.sales.show',$sale)->with('success','Sale posted and stock updated.');
    }
    public function show(Sale $sale) { $sale->load(['lines','location','creator']); $receiptSettings=ReceiptSetting::firstOrFail(); $receiptLogo=Documents::brandingLogo($receiptSettings); return view('safety-shop.sales.receipt',compact('sale','receiptSettings','receiptLogo')); }
}
