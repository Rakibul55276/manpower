<?php
namespace App\Modules\SafetyShop\Sales\Http\Controllers;
use App\Http\Controllers\Controller;
use App\Modules\SafetyShop\Shared\Models\Master;
use App\Modules\SafetyShop\Products\Models\Product;
use App\Modules\SafetyShop\Stock\Models\Stock;
use App\Modules\SafetyShop\Sales\Models\Sale;
use App\Modules\SafetyShop\Sales\Models\Customer;
use App\Modules\SafetyShop\Sales\Services\SaleService;
use App\Modules\SafetyShop\Sales\Models\SalePayment;
use App\Modules\SafetyShop\Sales\Services\SalePaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use App\Modules\SafetyShop\Shared\Models\ReceiptSetting;
use App\Services\Documents;
class SaleController extends Controller
{
    public function index(Request $request) { $sales=Sale::whereHas('location',fn($q)=>$q->where('company_id',$request->user()->company_id))->with(['location','creator'])->latest('id')->paginate(25); return view('safety-shop.sales.index',compact('sales')); }
    public function create(Request $request) { $companyId=$request->user()->company_id; $replacement=null;$initialCheckout=null;if($request->filled('replace')){abort_unless($request->user()->canApprove(),403);$replacement=Sale::with(['lines'])->whereKey((int)$request->input('replace'))->where('status','void')->whereHas('location',fn($q)=>$q->where('company_id',$companyId))->firstOrFail();abort_if($replacement->replacement()->exists(),422,'This invoice already has a replacement.');}$settings=ReceiptSetting::forCompany($companyId)->first(); $shopLocation=$replacement?$replacement->location:Master::where('company_id',$companyId)->where('type','location')->where('is_active',true)->when(optional($settings)->default_location_id,fn($query,$id)=>$query->whereKey($id))->first(); if(!$shopLocation)$shopLocation=Master::where('company_id',$companyId)->where('type','location')->where('is_active',true)->orderBy('name')->first(); $customers=Customer::where('company_id',$companyId)->where('is_active',true)->where('approval_status','approved')->orderBy('customer_type')->orderBy('name')->orderBy('phone')->get();if($replacement){$products=Product::whereIn('id',$replacement->lines->pluck('product_id'))->get()->keyBy('id');$stocks=Stock::where('location_id',$replacement->location_id)->whereIn('product_id',$products->keys())->pluck('quantity','product_id');$initialCheckout=['replacement_id'=>$replacement->id,'invoice_number'=>$replacement->invoice_number,'customer_id'=>$replacement->customer_id,'discount'=>\App\Services\Pay::decimal($replacement->discount_cents),'tax_rate'=>number_format($replacement->tax_rate_units/100,2,'.',''),'cash'=>\App\Services\Pay::decimal($replacement->cash_cents),'card'=>\App\Services\Pay::decimal($replacement->card_cents),'bank'=>\App\Services\Pay::decimal($replacement->bank_cents),'payment_received'=>$replacement->payment_received,'invoice_notes'=>$replacement->invoice_notes,'lines'=>$replacement->lines->map(function($line)use($products,$stocks){$p=$products->get($line->product_id);return $p?['id'=>$p->id,'sku'=>$p->sku,'name'=>$p->name,'barcode'=>$p->barcode,'unit'=>$p->unit,'price_cents'=>$p->price_cents,'available'=>(int)($stocks[$p->id]??0),'quantity'=>$line->quantity]:null;})->filter()->values()];}$requestKey=(string)Str::uuid(); return view('safety-shop.sales.checkout',compact('shopLocation','customers','requestKey','replacement','initialCheckout')); }
    public function editDraft(Request $request,Sale $sale)
    {
        $companyId=$request->user()->company_id;abort_unless($sale->status==='draft'&&(int)$sale->location()->value('company_id')===(int)$companyId,404);abort_unless($sale->created_by===$request->user()->id||$request->user()->canApprove(),403);
        $draft=$sale->load('lines');$replacement=null;$shopLocation=$sale->location;$customers=Customer::where('company_id',$companyId)->where('is_active',true)->where('approval_status','approved')->orderBy('name')->get();$products=Product::whereIn('id',$sale->lines->pluck('product_id'))->get()->keyBy('id');$stocks=Stock::where('location_id',$sale->location_id)->whereIn('product_id',$products->keys())->pluck('quantity','product_id');
        $initialCheckout=['customer_id'=>$sale->customer_id,'customer_type'=>$sale->customer_type,'customer'=>$sale->customer,'customer_phone'=>$sale->customer_phone,'customer_contact_person'=>$sale->customer_contact_person,'customer_email'=>$sale->customer_email,'customer_vat_number'=>$sale->customer_vat_number,'customer_commercial_registration'=>$sale->customer_commercial_registration,'customer_address'=>$sale->customer_address,'discount'=>\App\Services\Pay::decimal($sale->discount_cents),'tax_rate'=>number_format($sale->tax_rate_units/100,2,'.',''),'cash'=>\App\Services\Pay::decimal($sale->cash_cents),'card'=>\App\Services\Pay::decimal($sale->card_cents),'bank'=>\App\Services\Pay::decimal($sale->bank_cents),'payment_received'=>$sale->payment_received,'invoice_notes'=>$sale->invoice_notes,'lines'=>$sale->lines->map(function($line)use($products,$stocks){$p=$products->get($line->product_id);return $p?['id'=>$p->id,'sku'=>$p->sku,'name'=>$p->name,'barcode'=>$p->barcode,'unit'=>$p->unit,'price_cents'=>$p->price_cents,'available'=>(int)($stocks[$p->id]??0),'quantity'=>$line->quantity]:null;})->filter()->values()];$requestKey=(string)Str::uuid();
        return view('safety-shop.sales.checkout',compact('shopLocation','customers','requestKey','replacement','initialCheckout','draft'));
    }
    public function barcode(Request $r)
    {
        $data=$r->validate(['barcode'=>'required|string|max:100','location_id'=>'required|integer']);
        $companyId=$r->user()->company_id;
        abort_unless(Master::whereKey($data['location_id'])->where('company_id',$companyId)->where('type','location')->where('is_active',true)->exists(),422);
        $product=Product::where('company_id',$companyId)->where('barcode',$data['barcode'])->where('is_active',true)->firstOrFail();
        $stock=Stock::where('product_id',$product->id)->where('location_id',$data['location_id'])->value('quantity')??0;
        return response()->json(['id'=>$product->id,'sku'=>$product->sku,'name'=>$product->name,'barcode'=>$product->barcode,'unit'=>$product->unit,'price_cents'=>$product->price_cents,'available'=>(int)$stock]);
    }
    public function store(Request $r, SaleService $service)
    {
        if($r->filled('customer_id')){
            $customer=Customer::where('company_id',$r->user()->company_id)->whereKey($r->customer_id)->where('is_active',true)->firstOrFail();
            $r->merge(['customer_type'=>$customer->customer_type,'customer'=>$r->filled('customer')?$r->customer:$customer->name,'customer_name_ar'=>$customer->name_ar,'customer_contact_person'=>$r->filled('customer_contact_person')?$r->customer_contact_person:$customer->contact_person,'customer_phone'=>$r->filled('customer_phone')?$r->customer_phone:$customer->phone,'customer_email'=>$r->filled('customer_email')?$r->customer_email:$customer->email,'customer_vat_number'=>$r->filled('customer_vat_number')?$r->customer_vat_number:$customer->vat_number,'customer_commercial_registration'=>$r->filled('customer_commercial_registration')?$r->customer_commercial_registration:$customer->commercial_registration,'customer_address'=>$r->filled('customer_address')?$r->customer_address:$customer->address,'customer_building_number'=>$customer->building_number,'customer_street'=>$customer->street,'customer_district'=>$customer->district,'customer_city'=>$customer->city,'customer_postal_code'=>$customer->postal_code,'customer_country_code'=>$customer->country_code?:'SA']);
        }
        $r->merge(['customer_type'=>$r->input('customer_type','retail')]);
        $submitAction=$r->input('submit_action','final');abort_unless(in_array($submitAction,['draft','final'],true),422);$draftId=$r->filled('draft_id')?(int)$r->input('draft_id'):null;
        $money='nullable|regex:/^\d{1,8}(\.\d{1,2})?$/';
        $data=$r->validate(['request_key'=>'required|uuid','replacement_of_id'=>'nullable|integer|exists:safety_shop_sales,id','location_id'=>'required|integer','supply_date'=>'required|date|before_or_equal:today','customer_id'=>'nullable|integer|exists:safety_shop_customers,id','customer_type'=>'required|in:retail,company','payment_received'=>'nullable|boolean','invoice_notes'=>'nullable|string|max:250','customer'=>'nullable|string|max:150','customer_name_ar'=>'nullable|string|max:150','customer_contact_person'=>'nullable|string|max:150','customer_phone'=>['nullable','string','max:30','regex:/^[0-9+() .-]+$/'],'customer_email'=>'nullable|email|max:150','customer_vat_number'=>'nullable|digits:15','customer_commercial_registration'=>'nullable|string|max:30','customer_address'=>'nullable|string|max:500','customer_building_number'=>'nullable|string|max:20','customer_street'=>'nullable|string|max:150','customer_district'=>'nullable|string|max:100','customer_city'=>'nullable|string|max:100','customer_postal_code'=>'nullable|string|max:10','customer_country_code'=>'nullable|string|size:2','discount'=>$money,'tax_rate'=>'nullable|numeric|min:0|max:100','cash_paid'=>$money,'card_paid'=>$money,'bank_paid'=>$money,'payment_method'=>'nullable|in:cash,card,bank','paid'=>$money,'lines'=>'required|array|min:1|max:100','lines.*.product_id'=>'required|integer|distinct|exists:safety_shop_products,id','lines.*.quantity'=>'required|integer|min:1|max:1000000','lines.*.price_cents'=>'required|integer|min:0|max:9999999999']);
        abort_unless(Master::whereKey($data['location_id'])->where('company_id',$r->user()->company_id)->where('type','location')->where('is_active',true)->exists(),404);
        if($data['customer_type']==='company' && empty($data['customer_id'])) throw \Illuminate\Validation\ValidationException::withMessages(['customer_id'=>'Create and approve the bulk customer in Customer Master before selecting it in checkout.']);
        if(!empty($data['replacement_of_id'])){abort_unless($r->user()->canApprove(),403);$original=Sale::whereKey($data['replacement_of_id'])->where('status','void')->whereHas('location',fn($q)=>$q->where('company_id',$r->user()->company_id))->firstOrFail();abort_if($original->replacement()->exists(),422,'This invoice already has a replacement.');$data['replaces_sale_id']=$original->id;}
        $data['customer']=$data['customer']??'Walk-in customer';
        $draft=null;if($draftId){$draft=Sale::whereKey($draftId)->where('status','draft')->whereHas('location',fn($q)=>$q->where('company_id',$r->user()->company_id))->firstOrFail();abort_unless($draft->created_by===$r->user()->id||$r->user()->canApprove(),403);}
        if($submitAction==='draft'){$sale=$service->saveDraft($data,$r->user()->id,$draft);return redirect()->route('safety-shop.sales.show',$sale)->with('success','Invoice draft saved. Stock has not been deducted.');}
        $sale=$service->post($data,$r->user()->id);
        if($draft){$draft->lines()->delete();$draft->delete();\App\Models\ActivityLog::record('Finalized safety shop invoice draft',$sale->invoice_number);}
        $r->session()->put('safety_shop_location_id',(int)$data['location_id']);
        return redirect()->route('safety-shop.sales.show',$sale)->with('success','Sale posted and stock updated.');
    }
    public function cancelAndReplace(Request $request,Sale $sale,SaleService $service){abort_unless($request->user()->canApprove(),403);abort_unless((int)$sale->location()->value('company_id')===(int)$request->user()->company_id,404);$data=$request->validate(['cancel_reason'=>'required|string|min:5|max:1000']);$service->cancel($sale,$data['cancel_reason'],$request->user()->id);return redirect()->route('safety-shop.sales.create',['replace'=>$sale->id])->with('success','Original invoice voided and stock restored. Review and post the replacement invoice.');}
    public function show(Request $request,Sale $sale) { $companyId=$request->user()->company_id; abort_unless((int)$sale->location()->value('company_id')===(int)$companyId,404); $sale->load(['lines','location','creator','customerRecord','replacement','replaces','cancelledBy','payments.creator','payments.reversedBy']); $receiptSettings=Documents::branding(); abort_unless($receiptSettings,404); $receiptLogo=Documents::brandingLogo($receiptSettings); $receiptHeader=Documents::brandingAsset($receiptSettings->header_image_path); $receiptFooter=Documents::brandingAsset($receiptSettings->footer_image_path); $qr=$this->receiptQr($sale); return view('safety-shop.sales.receipt',compact('sale','receiptSettings','receiptLogo','receiptHeader','receiptFooter','qr')); }
    public function recordPayment(Request $request,Sale $sale,SalePaymentService $service)
    {
        abort_unless((int)$sale->location()->value('company_id')===(int)$request->user()->company_id,404);
        $data=$request->validate(['payment_date'=>'required|date|before_or_equal:today','method'=>'required|in:cash,card,bank','amount'=>'required|regex:/^\d{1,8}(\.\d{1,2})?$/','reference'=>'nullable|string|max:100','notes'=>'nullable|string|max:500']);
        $service->record($sale,$data,$request->user()->id);
        return redirect()->route('safety-shop.sales.show',$sale)->with('success','Payment recorded and invoice status updated.');
    }
    public function reversePayment(Request $request,Sale $sale,SalePayment $payment,SalePaymentService $service)
    {
        abort_unless($request->user()->canApprove(),403);abort_unless((int)$sale->location()->value('company_id')===(int)$request->user()->company_id && (int)$payment->sale_id===(int)$sale->id,404);
        $data=$request->validate(['reversal_reason'=>'required|string|min:5|max:500']);
        $service->reverse($payment,$data['reversal_reason'],$request->user()->id);
        return redirect()->route('safety-shop.sales.show',$sale)->with('success','Payment reversed and invoice status recalculated.');
    }
    public function pdf(Request $request,Sale $sale)
    {
        $companyId=$request->user()->company_id;
        abort_unless((int)$sale->location()->value('company_id')===(int)$companyId,404);
        $sale->load(['lines','location','creator','customerRecord']);
        $qr=$this->receiptQr($sale);
        $receiptSettings=Documents::branding();
        abort_unless($receiptSettings,404);
        $bytes=Documents::render('pdf.safety-shop-bulk-invoice',compact('sale','qr','receiptSettings'),'A4','portrait',false,false,true);
        return Documents::download($bytes,$sale->invoice_number.'.pdf');
    }
    private function receiptQr(Sale $sale)
    {
        $companyId=(int)$sale->location->company_id;
        $settings=Documents::branding();
        $fields=[
            1=>(string)(optional($settings)->company_name ?: 'Harbour Edge Company Limited'),
            2=>(string)(optional($settings)->vat_number ?: '314482276100003'),
            3=>$sale->created_at->copy()->utc()->format('Y-m-d\TH:i:s\Z'),
            4=>\App\Services\Pay::decimal($sale->total_cents),
            5=>\App\Services\Pay::decimal($sale->tax_cents),
        ];
        $tlv='';
        foreach($fields as $tag=>$value){$bytes=mb_strcut((string)$value,0,255,'UTF-8');$tlv.=chr($tag).chr(strlen($bytes)).$bytes;}
        return Documents::qrDataUri(base64_encode($tlv));
    }
}
