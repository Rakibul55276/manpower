<?php
namespace App\Modules\SafetyShop\Products\Http\Controllers;
use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Company;
use App\Modules\SafetyShop\Shared\Models\Master;
use App\Modules\SafetyShop\Products\Models\Product;
use App\Modules\SafetyShop\Stock\Models\Stock;
use App\Modules\SafetyShop\Products\Services\CatalogQuery;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
class ProductController extends Controller
{
    private function productsQuery(Request $r) { return (new CatalogQuery)->build($r); }
    public function index(Request $r)
    {
        $products = $this->productsQuery($r)->orderBy('name')->paginate(20)->withQueryString();
        $categories = Master::where('type','category')->orderBy('name')->get();
        $metrics = ['products'=>Product::where('is_active',true)->count(),'units'=>Stock::sum('quantity'),'value'=>Stock::join('safety_shop_products','product_id','=','safety_shop_products.id')->sum(DB::raw('quantity * cost_cents')),'low'=>Product::where('is_active',true)->whereRaw('(SELECT COALESCE(SUM(quantity),0) FROM safety_shop_stocks WHERE product_id = safety_shop_products.id) <= reorder_level')->count()];
        return view('safety-shop.products.index',compact('products','categories','metrics'));
    }
    public function create() { return $this->form(new Product(['unit'=>'piece','is_active'=>true,'reorder_level'=>0,'cost_cents'=>0,'price_cents'=>0])); }
    public function edit(Product $product) { return $this->form($product); }
    private function form($product)
    {
        $companyId=$product->company_id ?: request('company_id') ?: Company::where('is_active',true)->orderBy('id')->value('id');
        abort_unless($companyId,422,'Create an active company before adding Safety Shop products.');
        $categories=Master::where('company_id',$companyId)->where('type','category')->where(function($q)use($product){$q->where('is_active',true)->orWhere('id',$product->category_id ?: 0);})->orderBy('name')->get();
        $suggestions=[];
        foreach(['name','brand','size','unit','safety_standard'] as $field){
            $values=Product::where('company_id',$companyId)->whereNotNull($field)->where($field,'<>','')->distinct()->pluck($field);
            if(in_array($field,['brand','size','unit','safety_standard'],true)) $values=$values->merge(Master::where('company_id',$companyId)->where('type',$field)->where('is_active',true)->pluck('name'));
            $suggestions[$field]=$values->map(fn($value)=>trim((string)$value))->filter()->unique(fn($value)=>mb_strtolower($value))->sort(SORT_NATURAL|SORT_FLAG_CASE)->take(500)->values();
        }
        return view('safety-shop.products.form',compact('product','categories','suggestions','companyId'));
    }
    public function barcodeLookup(Request $request)
    {
        $data=$request->validate(['barcode'=>'required|string|max:100','company_id'=>'required|integer|exists:companies,id']);
        $product=Product::where('company_id',$data['company_id'])->where('barcode',$data['barcode'])->first();
        if(!$product)return response()->json(['found'=>false]);
        return response()->json(['found'=>true,'id'=>$product->id,'update_url'=>route('safety-shop.products.update',$product),'edit_url'=>route('safety-shop.products.edit',$product),'product'=>['barcode'=>$product->barcode,'sku'=>$product->sku,'name'=>$product->name,'brand'=>$product->brand,'size'=>$product->size,'unit'=>$product->unit,'safety_standard'=>$product->safety_standard,'category_id'=>$product->category_id,'reorder_level'=>$product->reorder_level,'cost'=>number_format($product->cost_cents/100,2,'.',''),'price'=>number_format($product->price_cents/100,2,'.',''),'is_active'=>$product->is_active?1:0,'notes'=>$product->notes]]);
    }
    public function skuSuggestion(Request $request)
    {
        $data=$request->validate(['company_id'=>'required|integer|exists:companies,id','category_id'=>'required|integer']);
        $category=Master::where('company_id',$data['company_id'])->where('type','category')->whereKey($data['category_id'])->firstOrFail();
        abort_unless($category->sku_prefix,422,'Set an SKU prefix for this category first.');
        $prefix=$category->sku_prefix;
        $numbers=Product::where('company_id',$data['company_id'])->where('sku','like',$prefix.'-%')->pluck('sku')->map(function($sku)use($prefix){return preg_match('/^'.preg_quote($prefix,'/').'-(\d+)$/',$sku,$m)?(int)$m[1]:0;});
        return response()->json(['sku'=>$prefix.'-'.str_pad($numbers->max()+1,4,'0',STR_PAD_LEFT),'prefix'=>$prefix]);
    }
    private function productData(Request $r, $id=null)
    {
        $companyId=$r->input('company_id');
        $data=$r->validate(['company_id'=>'required|integer|exists:companies,id','barcode'=>['nullable','string','max:100','regex:/^[A-Za-z0-9._-]+$/',Rule::unique('safety_shop_products')->where(fn($q)=>$q->where('company_id',$companyId))->ignore($id)],'sku'=>['required','string','max:50',Rule::unique('safety_shop_products')->where(fn($q)=>$q->where('company_id',$companyId))->ignore($id)],'name'=>'required|string|max:150','category_id'=>['nullable',Rule::exists('safety_shop_masters','id')->where(fn($q)=>$q->where('type','category')->where('company_id',$companyId))],'brand'=>'nullable|string|max:100','size'=>'nullable|string|max:50','unit'=>'required|string|max:30','safety_standard'=>'nullable|string|max:150','reorder_level'=>'required|integer|min:0|max:1000000','cost'=>'required|regex:/^\d{1,8}(\.\d{1,2})?$/','price'=>'required|regex:/^\d{1,8}(\.\d{1,2})?$/','is_active'=>'required|boolean','notes'=>'nullable|string|max:2000']);
        foreach (['cost','price'] as $key) { $parts=explode('.',$data[$key]); $data[$key.'_cents']=(int)$parts[0]*100+(int)str_pad($parts[1]??'',2,'0'); unset($data[$key]); }
        return $data;
    }
    public function store(Request $r) { $product=Product::create($this->productData($r)); ActivityLog::record('Created safety shop product',$product->sku); return redirect()->route('safety-shop.products.show',$product)->with('success','Product created. Receive stock to set its opening balance.'); }
    public function update(Request $r, Product $product)
    {
        $data=$this->productData($r,$product->id);
        DB::transaction(function () use ($product,$data) { Product::whereKey($product->id)->lockForUpdate()->firstOrFail()->update($data); ActivityLog::record('Updated safety shop product',$product->sku); });
        return redirect()->route('safety-shop.products.show',$product)->with('success','Product updated.');
    }
    public function show(Product $product)
    {
        $product->load(['category','stocks.location']); $movements=$product->movements()->with(['product','location','destination','supplier','creator'])->latest('id')->paginate(20);
        return view('safety-shop.products.show',compact('product','movements'));
    }

}
