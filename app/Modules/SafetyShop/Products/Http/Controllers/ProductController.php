<?php
namespace App\Modules\SafetyShop\Products\Http\Controllers;
use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Company;
use App\Modules\SafetyShop\Shared\Models\Master;
use App\Modules\SafetyShop\Shared\Models\ReceiptSetting;
use App\Modules\SafetyShop\Products\Models\Product;
use App\Modules\SafetyShop\Stock\Models\Stock;
use App\Modules\SafetyShop\Products\Services\CatalogQuery;
use App\Modules\SafetyShop\Products\Services\BarcodeGenerator;
use App\Modules\SafetyShop\Products\Services\Code128Barcode;
use App\Modules\SafetyShop\Products\Services\ProductPerformanceReport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Storage;
use App\Modules\SafetyShop\Tax\TaxDefinition;
class ProductController extends Controller
{
    public function __construct(
        private readonly CatalogQuery $catalogQuery,
        private readonly ProductPerformanceReport $performanceReport,
    ) {
    }

    private function productsQuery(Request $request)
    {
        return $this->catalogQuery->build($request);
    }

    public function index(Request $r)
    {
        $productsQuery = $this->productsQuery($r);
        [$periodStart,$periodEnd,$periodLabel]=$this->performanceReport->period($r);
        $products = $this->performanceReport->apply($productsQuery, $periodStart, $periodEnd)
            ->orderBy('safety_shop_products.name')->paginate($this->perPage())->withQueryString();
        $companyId=$r->user()->company_id;
        $categories = Master::where('company_id',$companyId)->where('type','category')->orderBy('name')->get();
        $metrics = ['products'=>Product::where('company_id',$companyId)->where('is_active',true)->count(),'units'=>Stock::whereHas('product',fn($q)=>$q->where('company_id',$companyId))->sum('quantity'),'value'=>Stock::join('safety_shop_products','product_id','=','safety_shop_products.id')->where('safety_shop_products.company_id',$companyId)->sum(DB::raw('quantity * cost_cents')),'low'=>Product::where('company_id',$companyId)->where('is_active',true)->whereRaw('(SELECT COALESCE(SUM(quantity),0) FROM safety_shop_stocks WHERE product_id = safety_shop_products.id) <= reorder_level')->count()];
        return view('safety-shop.products.index',compact('products','categories','metrics','periodLabel'));
    }
    public function create() { return $this->form(new Product(['unit'=>'piece','is_active'=>true,'reorder_level'=>0,'cost_cents'=>0,'price_cents'=>0,'price_includes_vat'=>false,'tax_category'=>'S','tax_rate_units'=>1500])); }
    public function edit(Product $product) { $this->authorizeCompany($product); return $this->form($product); }
    private function form($product)
    {
        $companyId=$product->company_id ?: auth()->user()->company_id;
        abort_unless($companyId,422,'Create an active company before adding Safety Shop products.');
        $categories=Master::where('company_id',$companyId)->where('type','category')->where(function($q)use($product){$q->where('is_active',true)->orWhere('id',$product->category_id ?: 0);})->orderBy('name')->get();
        $suggestions=[];
        foreach(['name','brand','size','variant','unit','safety_standard'] as $field){
            $values=Product::where('company_id',$companyId)->whereNotNull($field)->where($field,'<>','')->distinct()->pluck($field);
            if(in_array($field,['brand','size','variant','unit','safety_standard'],true)) $values=$values->merge(Master::where('company_id',$companyId)->where('type',$field)->where('is_active',true)->pluck('name'));
            $suggestions[$field]=$values->map(fn($value)=>trim((string)$value))->filter()->unique(fn($value)=>mb_strtolower($value))->sort(SORT_NATURAL|SORT_FLAG_CASE)->take(500)->values();
        }
        $taxCategories=TaxDefinition::CATEGORIES;$taxReasons=TaxDefinition::REASONS;
        $defaultLocation=$this->defaultLocation($companyId);
        $defaultLocationStock=$product->exists&&$defaultLocation?(int)(Stock::where('product_id',$product->id)->where('location_id',$defaultLocation->id)->value('quantity')??0):0;
        return view('safety-shop.products.form',compact('product','categories','suggestions','companyId','taxCategories','taxReasons','defaultLocation','defaultLocationStock'));
    }
    public function barcodeLookup(Request $request)
    {
        $request->merge(['company_id'=>$request->user()->company_id]);
        $data=$request->validate(['barcode'=>'required|string|max:100','company_id'=>'required|integer|exists:companies,id']);
        $product=Product::where('company_id',$data['company_id'])->where('barcode',$data['barcode'])->first();
        if(!$product)return response()->json(['found'=>false]);
        return response()->json(['found'=>true,'id'=>$product->id,'update_url'=>route('safety-shop.products.update',$product),'edit_url'=>route('safety-shop.products.edit',$product),'product'=>['barcode'=>$product->barcode,'sku'=>$product->sku,'name'=>$product->name,'brand'=>$product->brand,'size'=>$product->size,'variant'=>$product->variant,'unit'=>$product->unit,'safety_standard'=>$product->safety_standard,'category_id'=>$product->category_id,'reorder_level'=>$product->reorder_level,'cost'=>number_format($product->cost_cents/100,2,'.',''),'price'=>number_format($product->price_cents/100,2,'.',''),'price_includes_vat'=>$product->price_includes_vat?1:0,'tax_category'=>$product->tax_category,'tax_exemption_reason_code'=>$product->tax_exemption_reason_code,'tax_exemption_reason'=>$product->tax_exemption_reason,'is_active'=>$product->is_active?1:0,'notes'=>$product->notes]]);
    }
    public function skuSuggestion(Request $request)
    {
        $request->merge(['company_id'=>$request->user()->company_id]);
        $data=$request->validate(['company_id'=>'required|integer|exists:companies,id','category_id'=>'required|integer']);
        $category=Master::where('company_id',$data['company_id'])->where('type','category')->whereKey($data['category_id'])->firstOrFail();
        abort_unless($category->sku_prefix,422,'Set an SKU prefix for this category first.');
        $prefix=$category->sku_prefix;
        $numbers=Product::where('company_id',$data['company_id'])->where('sku','like',$prefix.'-%')->pluck('sku')->map(function($sku)use($prefix){return preg_match('/^'.preg_quote($prefix,'/').'-(\d+)$/',$sku,$m)?(int)$m[1]:0;});
        return response()->json(['sku'=>$prefix.'-'.str_pad($numbers->max()+1,4,'0',STR_PAD_LEFT),'prefix'=>$prefix]);
    }
    private function productData(Request $r, $id=null)
    {
        $companyId=(int)$r->user()->company_id; $r->merge(['company_id'=>$companyId]);
        $data=$r->validate(['company_id'=>'required|integer|exists:companies,id','barcode'=>['nullable','string','max:100','regex:/^[A-Za-z0-9._-]+$/',Rule::unique('safety_shop_products')->where(fn($q)=>$q->where('company_id',$companyId))->ignore($id)],'sku'=>['required','string','max:50',Rule::unique('safety_shop_products')->where(fn($q)=>$q->where('company_id',$companyId))->ignore($id)],'name'=>'required|string|max:255','category_id'=>['required',Rule::exists('safety_shop_masters','id')->where(fn($q)=>$q->where('type','category')->where('company_id',$companyId))],'brand'=>'nullable|string|max:100','size'=>'nullable|string|max:50','variant'=>'nullable|string|max:100','unit'=>'required|string|max:30','safety_standard'=>'nullable|string|max:150','reorder_level'=>'required|integer|min:0|max:1000000','cost'=>'required|regex:/^\d{1,8}(\.\d{1,2})?$/','price'=>'required|regex:/^\d{1,8}(\.\d{1,2})?$/','price_includes_vat'=>'required|boolean','tax_category'=>['required',Rule::in(array_keys(TaxDefinition::CATEGORIES))],'tax_exemption_reason_code'=>['nullable','required_unless:tax_category,S',Rule::in(array_keys(TaxDefinition::REASONS))],'tax_exemption_reason'=>'nullable|required_unless:tax_category,S|string|max:255','is_active'=>'required|boolean','notes'=>'nullable|string|max:2000']);
        $category=Master::where('company_id',$companyId)->where('type','category')->findOrFail($data['category_id']);
        $prefix=strtoupper((string)$category->sku_prefix);
        if(!$prefix || !preg_match('/^'.preg_quote($prefix,'/').'\-\d{4,}$/',strtoupper($data['sku']))){
            throw ValidationException::withMessages(['sku'=>"SKU must use the selected category prefix, for example {$prefix}-0001."]);
        }
        $data['sku']=strtoupper($data['sku']);
        $data['tax_rate_units']=TaxDefinition::rateFor($data['tax_category']);
        if($data['tax_category']==='S'){$data['tax_exemption_reason_code']=null;$data['tax_exemption_reason']=null;}
        if($data['tax_category']==='O'&&$data['tax_exemption_reason_code']!=='VATEX-SA-OOS')throw ValidationException::withMessages(['tax_exemption_reason_code'=>'Outside-scope supplies must use VATEX-SA-OOS.']);
        foreach (['cost','price'] as $key) { $parts=explode('.',$data[$key]); $data[$key.'_cents']=(int)$parts[0]*100+(int)str_pad($parts[1]??'',2,'0'); unset($data[$key]); }
        return $data;
    }
    public function store(Request $r)
    {
        $r->validate(['image'=>'nullable|image|mimes:jpeg,jpg,png,webp|max:4096']);
        $data=$this->productData($r);
        $imagePath=$r->file('image')?->store('safety-shop/products','public');if($imagePath)$data['image_path']=$imagePath;
        try{$product=DB::transaction(function () use ($data) {
            $product=Product::create($data);
            if(blank($product->barcode)){
                $product->barcode=(new BarcodeGenerator)->forProduct($product);
                $product->save();
            }
            if($location=$this->defaultLocation((int)$product->company_id))Stock::firstOrCreate(['product_id'=>$product->id,'location_id'=>$location->id],['quantity'=>0]);
            ActivityLog::record('Created safety shop product',$product->sku);
            return $product;
        });}catch(\Throwable $error){if($imagePath)Storage::disk('public')->delete($imagePath);throw $error;}
        return redirect()->route('safety-shop.products.show',$product)->with('success','Product created. Barcode '.$product->barcode.' is ready; receive stock to set its opening balance.');
    }
    public function update(Request $r, Product $product)
    {
        $this->authorizeCompany($product);
        $r->validate(['image'=>'nullable|image|mimes:jpeg,jpg,png,webp|max:4096','remove_image'=>'nullable|boolean']);
        $data=$this->productData($r,$product->id);
        $oldImage=$product->image_path;$newImage=$r->file('image')?->store('safety-shop/products','public');if($newImage)$data['image_path']=$newImage;elseif($r->boolean('remove_image'))$data['image_path']=null;
        try{DB::transaction(function () use ($product,$data) {
            $locked=Product::whereKey($product->id)->lockForUpdate()->firstOrFail();
            $locked->update($data);
            if(blank($locked->barcode)){
                $locked->barcode=(new BarcodeGenerator)->forProduct($locked);
                $locked->save();
            }
            if($location=$this->defaultLocation((int)$locked->company_id))Stock::firstOrCreate(['product_id'=>$locked->id,'location_id'=>$location->id],['quantity'=>0]);
            ActivityLog::record('Updated safety shop product',$locked->sku);
        });}catch(\Throwable $error){if($newImage)Storage::disk('public')->delete($newImage);throw $error;}
        if(($newImage||$r->boolean('remove_image'))&&$oldImage)Storage::disk('public')->delete($oldImage);
        return redirect()->route('safety-shop.products.show',$product)->with('success','Product updated.');
    }
    public function show(Product $product)
    {
        $this->authorizeCompany($product);
        $product->load(['category','stocks.location']); $movements=$product->movements()->with(['product','location','destination','supplier','creator'])->latest('id')->paginate($this->perPage())->withQueryString();
        return view('safety-shop.products.show',compact('product','movements'));
    }

    public function barcodeLabel(Request $request, Product $product, Code128Barcode $barcode)
    {
        $this->authorizeCompany($product);
        if (blank($product->barcode)) {
            return redirect()->route('safety-shop.products.show', $product)
                ->withErrors(['barcode' => 'A barcode is required before a barcode label can be printed. Edit the product and enter a barcode first.']);
        }
        $data = $request->validate([
            'copies' => 'nullable|integer|min:1|max:100',
            'format' => 'nullable|in:thermal-50x25,thermal-40x30,a4',
            'print' => 'nullable|boolean',
        ]);
        $copies = (int) ($data['copies'] ?? 1);
        $format = $data['format'] ?? 'thermal-50x25';
        $geometry = $barcode->geometry($product->barcode);

        return view('safety-shop.products.barcode-label', compact('product', 'copies', 'format', 'geometry'));
    }

    public function destroy(Product $product)
    {
        $this->authorizeCompany($product);
        abort_unless(auth()->user()->isCompanyAdmin(), 403);
        if ($reason = $this->deletionBlockReason($product)) {
            return back()->withErrors(['delete' => 'This product cannot be deleted because it '.$reason.'. First make it inactive, ensure every location has zero stock, and preserve products with transaction history for audit purposes.']);
        }
        $imagePath=$product->image_path;
        DB::transaction(function () use ($product) {
            $product->stocks()->delete();
            ActivityLog::record('Deleted unused safety shop product', $product->sku);
            $product->delete();
        });
        if($imagePath)Storage::disk('public')->delete($imagePath);
        return redirect()->route('safety-shop.products.index')->with('success', 'Unused product deleted.');
    }

    public function bulkDestroy(Request $request)
    {
        abort_unless($request->user()->isCompanyAdmin(), 403);
        $companyId = (int) $request->user()->company_id;
        $data = $request->validate([
            'products' => ['required', 'array', 'min:2', 'max:100'],
            'products.*' => ['required', 'integer', 'distinct', Rule::exists('safety_shop_products', 'id')->where(fn ($query) => $query->where('company_id', $companyId))],
        ]);

        $deleted = 0;
        $skipped = [];
        $imagePaths = [];

        DB::transaction(function () use ($data, $companyId, &$deleted, &$skipped, &$imagePaths) {
            $products = Product::where('company_id', $companyId)
                ->whereIn('id', $data['products'])
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            foreach ($products as $product) {
                if ($reason = $this->deletionBlockReason($product)) {
                    $skipped[] = $product->sku.' ('.$reason.')';
                    continue;
                }

                $product->stocks()->delete();
                ActivityLog::record('Deleted unused safety shop product', $product->sku);
                if ($product->image_path) $imagePaths[] = $product->image_path;
                $product->delete();
                $deleted++;
            }
        });

        foreach ($imagePaths as $imagePath) Storage::disk('public')->delete($imagePath);

        $redirect = redirect()->route('safety-shop.products.index');
        if ($deleted > 0) $redirect->with('success', $deleted.' unused '.($deleted === 1 ? 'product' : 'products').' deleted.');
        if ($skipped) {
            $redirect->withErrors(['delete' => count($skipped).' selected '.(count($skipped) === 1 ? 'product was' : 'products were').' skipped: '.implode(', ', $skipped).'. Only inactive products with zero stock at every location and no transaction history can be deleted.']);
        }
        return $redirect;
    }

    private function deletionBlockReason(Product $product): ?string
    {
        if ($product->is_active) return 'is still active';
        if ($product->stocks()->where('quantity', '<>', 0)->exists()) return 'has non-zero stock at one or more locations';
        if ($product->movements()->exists()
            || DB::table('safety_shop_sale_lines')->where('product_id', $product->id)->exists()
            || DB::table('commercial_document_lines')->where('product_id', $product->id)->exists()) {
            return 'has transaction history';
        }
        return null;
    }

    private function authorizeCompany(Product $product): void { abort_unless((int)$product->company_id===(int)auth()->user()->company_id,404); }

    private function defaultLocation(int $companyId)
    {
        $settings=ReceiptSetting::forCompany($companyId)->first();
        $location=Master::where('company_id',$companyId)->where('type','location')->where('is_active',true)
            ->when(optional($settings)->default_location_id,fn($query,$id)=>$query->whereKey($id))->first();
        return $location?:Master::where('company_id',$companyId)->where('type','location')->where('is_active',true)->orderBy('name')->first();
    }

}
