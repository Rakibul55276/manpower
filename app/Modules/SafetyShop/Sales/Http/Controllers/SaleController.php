<?php
namespace App\Modules\SafetyShop\Sales\Http\Controllers;
use App\Http\Controllers\Controller;
use App\Modules\SafetyShop\Shared\Models\Master;
use App\Modules\SafetyShop\Products\Models\Product;
use App\Modules\SafetyShop\Products\Services\BarcodeGenerator;
use App\Modules\SafetyShop\Stock\Models\Stock;
use App\Modules\SafetyShop\Stock\Services\InventoryService;
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
use App\Models\CommercialDocument;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;
use App\Models\ActivityLog;
use App\Modules\SafetyShop\Tax\TaxDefinition;
use App\Modules\Zatca\Services\ZatcaIssuanceGate;
use App\Modules\Zatca\Services\ZatcaQrService;
use App\Modules\Zatca\Services\ZatcaLifecycleService;
class SaleController extends Controller
{
    public function index(Request $request) { $search=trim((string)$request->input('q'));$sales=Sale::whereHas('location',fn($q)=>$q->where('company_id',$request->user()->company_id))->when($search,fn($q)=>$q->where(function($query)use($search){$query->where('invoice_number','like','%'.$search.'%')->orWhere('customer_po_number','like','%'.$search.'%')->orWhere('customer','like','%'.$search.'%');}))->with(['location','creator'])->latest('id')->paginate($this->perPage())->withQueryString(); return view('safety-shop.sales.index',compact('sales','search')); }
    public function create(Request $request)
    {
        $companyId=$request->user()->company_id;$replacement=null;$initialCheckout=null;
        if($request->filled('replace')){abort_unless($request->user()->canApprove(),403);$replacement=Sale::with('lines')->whereKey((int)$request->input('replace'))->where('status','void')->whereHas('location',fn($q)=>$q->where('company_id',$companyId))->firstOrFail();abort_if($replacement->replacement()->exists(),422,'This invoice already has a replacement.');}
        $settings=ReceiptSetting::forCompany($companyId)->first();
        $shopLocation=$replacement?$replacement->location:Master::where('company_id',$companyId)->where('type','location')->where('is_active',true)->when(optional($settings)->default_location_id,fn($query,$id)=>$query->whereKey($id))->first();
        if(!$shopLocation)$shopLocation=Master::where('company_id',$companyId)->where('type','location')->where('is_active',true)->orderBy('name')->first();
        $customers=Customer::where('company_id',$companyId)->where('is_active',true)->where('approval_status','approved')->orderBy('customer_type')->orderBy('name')->orderBy('phone')->get();
        $quotations=CommercialDocument::where('company_id',$companyId)->where('type','quotation')->where('status','approved')->whereNull('superseded_by_id')->with('customer')->latest('approved_at')->get();
        if($replacement){$products=Product::whereIn('id',$replacement->lines->pluck('product_id'))->get()->keyBy('id');$stocks=Stock::where('location_id',$replacement->location_id)->whereIn('product_id',$products->keys())->pluck('quantity','product_id');$initialCheckout=['replacement_id'=>$replacement->id,'invoice_number'=>$replacement->invoice_number,'customer_id'=>$replacement->customer_id,'discount'=>\App\Services\Pay::decimal($replacement->discount_cents),'cash'=>\App\Services\Pay::decimal($replacement->cash_cents),'card'=>\App\Services\Pay::decimal($replacement->card_cents),'bank'=>\App\Services\Pay::decimal($replacement->bank_cents),'payment_received'=>$replacement->payment_received,'invoice_notes'=>$replacement->invoice_notes,'lines'=>$replacement->lines->map(function($line)use($products,$stocks){$p=$products->get($line->product_id);return $p?['id'=>$p->id,'sku'=>$line->sku,'name'=>$line->name,'barcode'=>$line->barcode,'unit'=>$line->unit,'price_cents'=>$line->price_cents,'price_includes_vat'=>$line->price_includes_vat,'tax_category'=>$line->tax_category,'tax_rate_units'=>$line->tax_rate_units,'available'=>(int)($stocks[$p->id]??0),'quantity'=>$line->quantity]:null;})->filter()->values()];}
        $requestKey=(string)Str::uuid();
        $categories=Master::where('company_id',$companyId)->where('type','category')->where('is_active',true)->orderBy('name')->get();
        $catalogProducts=$this->checkoutCatalog($companyId,$shopLocation);
        $productSuggestions=$this->checkoutProductSuggestions($companyId);
        return view('safety-shop.sales.checkout',compact('shopLocation','customers','quotations','categories','catalogProducts','productSuggestions','requestKey','replacement','initialCheckout'));
    }

    public function quotationData(Request $request, CommercialDocument $quotation)
    {
        $companyId=(int)$request->user()->company_id;
        abort_unless((int)$quotation->company_id===$companyId&&$quotation->type==='quotation'&&$quotation->status==='approved'&&!$quotation->superseded_by_id,404);
        $data=$request->validate(['location_id'=>'required|integer']);
        abort_unless(Master::whereKey($data['location_id'])->where('company_id',$companyId)->where('type','location')->where('is_active',true)->exists(),422);
        $quotation->load(['customer','lines']);
        if($quotation->lines->contains(fn($line)=>empty($line->product_id)))return response()->json(['message'=>'This quotation contains an item that is not linked to a stock product. Link every quoted item to a product before invoicing.'],422);
        $productIds=$quotation->lines->pluck('product_id')->unique();
        $products=Product::where('company_id',$companyId)->where('is_active',true)->whereIn('id',$productIds)->get()->keyBy('id');
        if($products->count()!==$productIds->count())return response()->json(['message'=>'One or more quoted products are inactive or unavailable.'],422);
        $stocks=Stock::where('location_id',$data['location_id'])->whereIn('product_id',$productIds)->pluck('quantity','product_id');
        $invoiceLines=$quotation->lines->groupBy('product_id')->map(function($group,$productId)use($products,$stocks){$p=$products->get($productId);$signatures=$group->map(fn($line)=>implode('|',[$line->unit_price_cents,(int)$line->price_includes_vat,$line->tax_category,$line->tax_rate_units,$line->tax_exemption_reason_code]))->unique();if($signatures->count()>1)abort(422,'Repeated quotation rows for the same product must use one price and VAT treatment.');$line=$group->first();return ['id'=>$p->id,'sku'=>$p->sku,'name'=>$line->description,'barcode'=>$p->barcode,'unit'=>$p->unit,'price_cents'=>(int)$line->unit_price_cents,'price_includes_vat'=>(bool)$line->price_includes_vat,'tax_category'=>$line->tax_category,'tax_rate_units'=>(int)$line->tax_rate_units,'available'=>(int)($stocks[$p->id]??0),'quantity'=>(int)$group->sum('quantity')];})->values();
        $shortages=$invoiceLines->filter(fn($line)=>$line['available']<$line['quantity'])->map(fn($line)=>$line['name'].' (required '.$line['quantity'].', available '.$line['available'].')')->values();
        if($shortages->isNotEmpty())return response()->json(['message'=>'Invoice cannot be generated because quoted stock is unavailable at this location: '.$shortages->implode('; ').'.'],422);
        return response()->json(['id'=>$quotation->id,'number'=>$quotation->number,'customer_id'=>$quotation->customer_id,'reference'=>$quotation->reference,'customer_po_number'=>$quotation->reference,'discount'=>\App\Services\Pay::decimal($quotation->discount_cents),'notes'=>$quotation->notes,'lines'=>$invoiceLines]);
    }
    public function editDraft(Request $request,Sale $sale)
    {
        $companyId=$request->user()->company_id;abort_unless($sale->status==='draft'&&(int)$sale->location()->value('company_id')===(int)$companyId,404);abort_unless($sale->created_by===$request->user()->id||$request->user()->canApprove(),403);
        $draft=$sale->load('lines');$replacement=null;$shopLocation=$sale->location;$customers=Customer::where('company_id',$companyId)->where('is_active',true)->where('approval_status','approved')->orderBy('name')->get();$products=Product::whereIn('id',$sale->lines->pluck('product_id'))->get()->keyBy('id');$stocks=Stock::where('location_id',$sale->location_id)->whereIn('product_id',$products->keys())->pluck('quantity','product_id');
        $initialCheckout=['customer_id'=>$sale->customer_id,'customer_type'=>$sale->customer_type,'customer'=>$sale->customer,'customer_phone'=>$sale->customer_phone,'customer_contact_person'=>$sale->customer_contact_person,'customer_email'=>$sale->customer_email,'customer_vat_number'=>$sale->customer_vat_number,'customer_commercial_registration'=>$sale->customer_commercial_registration,'customer_address'=>$sale->customer_address,'discount'=>\App\Services\Pay::decimal($sale->discount_cents),'cash'=>\App\Services\Pay::decimal($sale->cash_cents),'card'=>\App\Services\Pay::decimal($sale->card_cents),'bank'=>\App\Services\Pay::decimal($sale->bank_cents),'payment_received'=>$sale->payment_received,'invoice_notes'=>$sale->invoice_notes,'lines'=>$sale->lines->map(function($line)use($products,$stocks){$p=$products->get($line->product_id);return $p?['id'=>$p->id,'sku'=>$line->sku,'name'=>$line->name,'barcode'=>$line->barcode,'unit'=>$line->unit,'price_cents'=>$line->price_cents,'price_includes_vat'=>$line->price_includes_vat,'tax_category'=>$line->tax_category,'tax_rate_units'=>$line->tax_rate_units,'available'=>(int)($stocks[$p->id]??0),'quantity'=>$line->quantity]:null;})->filter()->values()];$requestKey=(string)Str::uuid();
        $initialCheckout['quotation_id']=$sale->quotation_id;
        $categories=Master::where('company_id',$companyId)->where('type','category')->where('is_active',true)->orderBy('name')->get();
        $catalogProducts=$this->checkoutCatalog($companyId,$shopLocation);
        $productSuggestions=$this->checkoutProductSuggestions($companyId);
        $quotations=collect();
        return view('safety-shop.sales.checkout',compact('shopLocation','customers','quotations','categories','catalogProducts','productSuggestions','requestKey','replacement','initialCheckout','draft'));
    }
    private function checkoutCatalog(int $companyId, $location)
    {
        if(!$location)return collect();
        return Product::where('company_id',$companyId)->where('is_active',true)
            ->whereHas('stocks',fn($stock)=>$stock->where('location_id',$location->id))
            ->with(['stocks'=>fn($q)=>$q->where('location_id',$location->id)])
            ->orderBy('name')->limit(250)->get()
            ->map(function($product){$product->checkout_available=(int)optional($product->stocks->first())->quantity;return $product;});
    }
    private function checkoutProductSuggestions(int $companyId): array
    {
        $suggestions=[];
        foreach(['brand','size','variant','safety_standard'] as $field){
            $values=Product::where('company_id',$companyId)->whereNotNull($field)->where($field,'<>','')->distinct()->pluck($field);
            $values=$values->merge(Master::where('company_id',$companyId)->where('type',$field)->where('is_active',true)->pluck('name'));
            $suggestions[$field]=$values->map(fn($value)=>trim((string)$value))->filter()->unique(fn($value)=>mb_strtolower($value))->sort(SORT_NATURAL|SORT_FLAG_CASE)->take(500)->values();
        }
        return $suggestions;
    }
    public function quickCustomer(Request $request)
    {
        if($request->filled('phone'))$request->merge(['phone'=>preg_replace('/[^0-9+]/','',(string)$request->phone)]);
        $companyId=(int)$request->user()->company_id;
        $data=$request->validate([
            'customer_type'=>'required|in:retail,company','name'=>'required|string|max:150','name_ar'=>'nullable|required_if:customer_type,company|string|max:150',
            'contact_person'=>'nullable|required_if:customer_type,company|string|max:150','phone'=>['nullable','string','max:30',Rule::unique('safety_shop_customers','phone')->where(fn($q)=>$q->where('company_id',$companyId))],
            'email'=>'nullable|email|max:150','vat_number'=>'nullable|required_if:customer_type,company|digits:15','commercial_registration'=>'nullable|string|max:30',
            'address'=>'nullable|string|max:500','building_number'=>'nullable|digits:4','additional_number'=>'nullable|digits:4','street'=>'nullable|string|max:150',
            'district'=>'nullable|string|max:100','city'=>'nullable|string|max:100','postal_code'=>'nullable|digits:5','country_code'=>'required|string|size:2',
            'bank_account_name'=>'nullable|string|max:150','bank_name'=>'nullable|string|max:150','bank_account_number'=>'nullable|string|max:50','bank_iban'=>'nullable|string|max:34','bank_branch'=>'nullable|string|max:100',
        ]);
        $customer=Customer::create($data+['company_id'=>$companyId,'is_active'=>true,'approval_status'=>'approved','approved_by'=>$request->user()->id,'approved_at'=>now()]);
        ActivityLog::record('Quick-created safety shop customer',$customer->name);
        return response()->json(['message'=>'Customer saved and selected.','customer'=>$customer]);
    }
    public function quickProduct(Request $request,InventoryService $inventory)
    {
        $companyId=(int)$request->user()->company_id;
        $base=$request->validate([
            'location_id'=>['required',Rule::exists('safety_shop_masters','id')->where(fn($q)=>$q->where('company_id',$companyId)->where('type','location')->where('is_active',true))],
            'barcode'=>['required','string','max:100','regex:/^[A-Za-z0-9._-]+$/'],'quantity'=>'required|integer|min:1|max:1000000',
        ]);
        $existing=Product::where('company_id',$companyId)->where('barcode',$base['barcode'])->where('is_active',true)->first();
        $canEditProduct=$request->user()->isCompanyAdmin();
        $data=($existing&&!$canEditProduct)?$base:$request->validate([
            'location_id'=>['required',Rule::exists('safety_shop_masters','id')->where(fn($q)=>$q->where('company_id',$companyId)->where('type','location')->where('is_active',true))],
            'barcode'=>['required','string','max:100','regex:/^[A-Za-z0-9._-]+$/',Rule::unique('safety_shop_products','barcode')->where(fn($q)=>$q->where('company_id',$companyId))->ignore(optional($existing)->id)],
            'name'=>'required|string|max:255','category_id'=>['required',Rule::exists('safety_shop_masters','id')->where(fn($q)=>$q->where('company_id',$companyId)->where('type','category')->where('is_active',true))],
            'unit'=>'required|string|max:30','quantity'=>'required|integer|min:1|max:1000000','cost'=>'required|regex:/^\d{1,8}(\.\d{1,2})?$/','price'=>'required|regex:/^\d{1,8}(\.\d{1,2})?$/',
            'price_includes_vat'=>'required|boolean','brand'=>'nullable|string|max:100','size'=>'nullable|string|max:50','variant'=>'nullable|string|max:100','safety_standard'=>'nullable|string|max:150',
            'reorder_level'=>'nullable|integer|min:0|max:1000000','notes'=>'nullable|string|max:2000',
        ]);
        $productData=null;if(!$existing||$canEditProduct){$productData=$data;foreach(['location_id','quantity','cost','price'] as $key)unset($productData[$key]);foreach(['cost','price'] as $key){$parts=explode('.',$data[$key]);$productData[$key.'_cents']=(int)$parts[0]*100+(int)str_pad($parts[1]??'',2,'0');}}
        if(!$existing){$category=Master::whereKey($data['category_id'])->where('company_id',$companyId)->where('type','category')->firstOrFail();if(!$category->sku_prefix)throw ValidationException::withMessages(['category_id'=>'Set an SKU prefix for this category in Category Master first.']);$prefix=strtoupper($category->sku_prefix);$max=Product::where('company_id',$companyId)->where('sku','like',$prefix.'-%')->pluck('sku')->map(fn($sku)=>preg_match('/^'.preg_quote($prefix,'/').'-(\d+)$/i',$sku,$m)?(int)$m[1]:0)->max();$productData+=['company_id'=>$companyId,'sku'=>$prefix.'-'.str_pad($max+1,4,'0',STR_PAD_LEFT),'tax_category'=>'S','tax_rate_units'=>TaxDefinition::rateFor('S'),'is_active'=>true,'reorder_level'=>$data['reorder_level']??0];}
        $product=DB::transaction(function()use($productData,$existing,$data,$companyId,$request,$inventory){
            if($existing){if($productData)$existing->update($productData);$product=$existing->fresh();}else{$product=Product::create($productData);}
            $inventory->post(['company_id'=>$companyId,'request_key'=>(string)Str::uuid(),'type'=>'receipt','product_id'=>$product->id,'location_id'=>(int)$data['location_id'],'destination_id'=>null,'supplier_id'=>null,'quantity'=>(int)$data['quantity'],'movement_date'=>now()->toDateString(),'reference'=>'Checkout quick add','recipient'=>null,'notes'=>'Opening stock added during barcode checkout'],$request->user()->id);
            ActivityLog::record($existing?'Updated product and received stock from checkout':'Quick-created safety shop product',$product->sku);return $product;
        });
        $available=(int)(Stock::where('product_id',$product->id)->where('location_id',$data['location_id'])->value('quantity')??0);
        return response()->json(['message'=>$existing?'Stock received and product added to the cart.':'Product and opening stock added to the cart.','product'=>['id'=>$product->id,'sku'=>$product->sku,'name'=>$product->name,'barcode'=>$product->barcode,'unit'=>$product->unit,'price_cents'=>$product->price_cents,'price_includes_vat'=>$product->price_includes_vat,'tax_category'=>$product->tax_category,'tax_rate_units'=>$product->tax_rate_units,'available'=>$available]]);
    }
    public function barcode(Request $r)
    {
        $data=$r->validate(['barcode'=>'required|string|max:100','location_id'=>'required|integer']);
        $companyId=$r->user()->company_id;
        abort_unless(Master::whereKey($data['location_id'])->where('company_id',$companyId)->where('type','location')->where('is_active',true)->exists(),422);
        $product=Product::where('company_id',$companyId)->where('barcode',$data['barcode'])->where('is_active',true)->firstOrFail();
        $stock=Stock::where('product_id',$product->id)->where('location_id',$data['location_id'])->value('quantity')??0;
        return response()->json(['id'=>$product->id,'sku'=>$product->sku,'name'=>$product->name,'barcode'=>$product->barcode,'category_id'=>$product->category_id,'brand'=>$product->brand,'size'=>$product->size,'variant'=>$product->variant,'unit'=>$product->unit,'safety_standard'=>$product->safety_standard,'reorder_level'=>$product->reorder_level,'cost_cents'=>$product->cost_cents,'price_cents'=>$product->price_cents,'price_includes_vat'=>$product->price_includes_vat,'tax_category'=>$product->tax_category,'tax_rate_units'=>$product->tax_rate_units,'notes'=>$product->notes,'available'=>(int)$stock]);
    }
    public function store(Request $r, SaleService $service,ZatcaLifecycleService $zatca)
    {
        if($r->filled('quotation_id')){
            $sourceQuotation=CommercialDocument::whereKey($r->quotation_id)->where('company_id',$r->user()->company_id)->where('type','quotation')->where('status','approved')->whereNull('superseded_by_id')->firstOrFail();
            $r->merge(['customer_id'=>$sourceQuotation->customer_id]);
        }
        if($r->filled('customer_id')){
            $customer=Customer::where('company_id',$r->user()->company_id)->whereKey($r->customer_id)->where('is_active',true)->firstOrFail();
            $r->merge(['customer_type'=>$customer->customer_type,'customer'=>$customer->name,'customer_name_ar'=>$customer->name_ar,'customer_contact_person'=>$customer->contact_person,'customer_phone'=>$customer->phone,'customer_email'=>$customer->email,'customer_vat_number'=>$customer->vat_number,'customer_commercial_registration'=>$customer->commercial_registration,'customer_address'=>$customer->address,'customer_building_number'=>$customer->building_number,'customer_additional_number'=>$customer->additional_number,'customer_street'=>$customer->street,'customer_district'=>$customer->district,'customer_city'=>$customer->city,'customer_postal_code'=>$customer->postal_code,'customer_country_code'=>$customer->country_code?:'SA']);
        }
        $r->merge(['customer_type'=>$r->input('customer_type','retail')]);
        $submitAction=$r->input('submit_action','final');abort_unless(in_array($submitAction,['draft','final'],true),422);$draftId=$r->filled('draft_id')?(int)$r->input('draft_id'):null;
        $money='nullable|regex:/^\d{1,8}(\.\d{1,2})?$/';
        $data=$r->validate(['request_key'=>'required|uuid','replacement_of_id'=>'nullable|integer|exists:safety_shop_sales,id','location_id'=>'required|integer','supply_date'=>'required|date|before_or_equal:today','due_date'=>'nullable|date|after_or_equal:supply_date','customer_id'=>'nullable|integer|exists:safety_shop_customers,id','customer_type'=>'required|in:retail,company','payment_received'=>'nullable|boolean','customer_po_number'=>'nullable|string|max:100','invoice_notes'=>'nullable|string|max:250','customer'=>'nullable|string|max:150','customer_name_ar'=>'nullable|string|max:150','customer_contact_person'=>'nullable|string|max:150','customer_phone'=>['nullable','string','max:30','regex:/^[0-9+() .-]+$/'],'customer_email'=>'nullable|email|max:150','customer_vat_number'=>'nullable|digits:15','customer_commercial_registration'=>'nullable|alpha_num|max:30','customer_address'=>'nullable|string|max:500','customer_building_number'=>'nullable|digits:4','customer_additional_number'=>'nullable|digits:4','customer_street'=>'nullable|string|max:150','customer_district'=>'nullable|string|max:100','customer_city'=>'nullable|string|max:100','customer_postal_code'=>'nullable|digits:5','customer_country_code'=>'nullable|string|size:2','discount'=>$money,'tax_rate'=>'nullable|numeric|min:0|max:100','cash_paid'=>$money,'card_paid'=>$money,'bank_paid'=>$money,'payment_method'=>'nullable|in:cash,card,bank','paid'=>$money,'lines'=>'required|array|min:1|max:100','lines.*.product_id'=>'required|integer|distinct|exists:safety_shop_products,id','lines.*.quantity'=>'required|integer|min:1|max:1000000','lines.*.price_cents'=>'required|integer|min:0|max:9999999999']);
        $quotationInput=$r->validate(['quotation_id'=>['nullable','integer',Rule::exists('commercial_documents','id')->where(fn($q)=>$q->where('company_id',$r->user()->company_id)->where('type','quotation')->where('status','approved')->whereNull('superseded_by_id'))]]);
        $data['quotation_id']=$quotationInput['quotation_id']??null;
        abort_unless(Master::whereKey($data['location_id'])->where('company_id',$r->user()->company_id)->where('type','location')->where('is_active',true)->exists(),404);
        if($data['customer_type']==='company' && empty($data['customer_id'])) throw \Illuminate\Validation\ValidationException::withMessages(['customer_id'=>'Create and approve the bulk customer in Customer Master before selecting it in checkout.']);
        if(!empty($data['replacement_of_id'])){abort_unless($r->user()->canApprove(),403);$original=Sale::whereKey($data['replacement_of_id'])->where('status','void')->whereHas('location',fn($q)=>$q->where('company_id',$r->user()->company_id))->firstOrFail();abort_if($original->replacement()->exists(),422,'This invoice already has a replacement.');$data['replaces_sale_id']=$original->id;}
        $data['customer']=$data['customer']??'Retail Customer';
        $draft=null;if($draftId){$draft=Sale::whereKey($draftId)->where('status','draft')->whereHas('location',fn($q)=>$q->where('company_id',$r->user()->company_id))->firstOrFail();abort_unless($draft->created_by===$r->user()->id||$r->user()->canApprove(),403);}
        if($submitAction==='draft'){$sale=$service->saveDraft($data,$r->user()->id,$draft);return redirect()->route('safety-shop.sales.show',$sale)->with('success','Invoice draft saved. Stock has not been deducted.');}
        $sale=$service->post($data,$r->user()->id);
        $zatcaProcessed=$zatca->processSale($sale);
        if($draft){$draft->lines()->delete();$draft->delete();\App\Models\ActivityLog::record('Finalized safety shop invoice draft',$sale->invoice_number);}
        $r->session()->put('safety_shop_location_id',(int)$data['location_id']);
        return redirect()->route('safety-shop.sales.show',$sale)->with('success','Sale posted and stock updated.'.($zatcaProcessed?' ZATCA accepted the document.':''));
    }
    public function cancelAndReplace(Request $request,Sale $sale,SaleService $service){abort_unless($request->user()->canApprove(),403);abort_unless((int)$sale->location()->value('company_id')===(int)$request->user()->company_id,404);$data=$request->validate(['cancel_reason'=>'required|string|min:5|max:1000']);$service->cancel($sale,$data['cancel_reason'],$request->user()->id);return redirect()->route('safety-shop.sales.create',['replace'=>$sale->id])->with('success','Original invoice voided and stock restored. Review and post the replacement invoice.');}
    public function show(Request $request,Sale $sale,ZatcaQrService $qrService) { $companyId=$request->user()->company_id; abort_unless((int)$sale->location()->value('company_id')===(int)$companyId,404); $sale->load(['lines','location','creator','customerRecord','replacement','replaces','cancelledBy','payments.creator','payments.reversedBy','zatcaDocument']); $receiptSettings=Documents::branding(); abort_unless($receiptSettings,404); $receiptLogo=Documents::brandingLogo($receiptSettings); $receiptHeader=Documents::brandingAsset($receiptSettings->header_image_path); $receiptFooter=Documents::brandingAsset($receiptSettings->footer_image_path); $qr=$qrService->dataUri($sale->zatcaDocument,$this->receiptQrPayload($sale)); return view('safety-shop.sales.receipt',compact('sale','receiptSettings','receiptLogo','receiptHeader','receiptFooter','qr')); }
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
    public function pdf(Request $request,Sale $sale,ZatcaIssuanceGate $gate,ZatcaQrService $qrService)
    {
        $companyId=$request->user()->company_id;
        abort_unless((int)$sale->location()->value('company_id')===(int)$companyId,404);
        $sale->load(['lines','location','creator','customerRecord','zatcaDocument']);
        $gate->assertPrintable((int)$companyId,$sale->invoice_type,$sale->zatcaDocument,$sale->status==='draft');
        $qr=$qrService->dataUri($sale->zatcaDocument,$this->receiptQrPayload($sale));
        $receiptSettings=Documents::branding();
        abort_unless($receiptSettings,404);
        $bytes=Documents::render('pdf.safety-shop-bulk-invoice',compact('sale','qr','receiptSettings'),'A4','portrait',false,false,true);
        return Documents::download($bytes,$sale->invoice_number.'.pdf');
    }
    private function receiptQrPayload(Sale $sale): string
    {
        $companyId=(int)$sale->location->company_id;
        $settings=ReceiptSetting::forCompany($companyId)->firstOrFail();
        $fields=[
            1=>(string)$settings->company_name,
            2=>(string)$settings->vat_number,
            3=>$sale->created_at->copy()->utc()->format('Y-m-d\TH:i:s\Z'),
            4=>\App\Services\Pay::decimal($sale->total_cents),
            5=>\App\Services\Pay::decimal($sale->tax_cents),
        ];
        $tlv='';
        foreach($fields as $tag=>$value){$bytes=mb_strcut((string)$value,0,255,'UTF-8');$tlv.=chr($tag).chr(strlen($bytes)).$bytes;}
        return base64_encode($tlv);
    }
}
