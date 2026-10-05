<?php
namespace App\Modules\SafetyShop\Products\Http\Controllers;
use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
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
    private function form($product) { $categories=Master::where('type','category')->orderBy('name')->get(); return view('safety-shop.products.form',compact('product','categories')); }
    private function productData(Request $r, $id=null)
    {
        $data=$r->validate(['barcode'=>['nullable','string','max:100','regex:/^[A-Za-z0-9._-]+$/',Rule::unique('safety_shop_products')->ignore($id)],'sku'=>['required','string','max:50',Rule::unique('safety_shop_products')->ignore($id)],'name'=>'required|string|max:150','category_id'=>['nullable',Rule::exists('safety_shop_masters','id')->where('type','category')],'brand'=>'nullable|string|max:100','size'=>'nullable|string|max:50','unit'=>'required|string|max:30','safety_standard'=>'nullable|string|max:150','reorder_level'=>'required|integer|min:0|max:1000000','cost'=>'required|regex:/^\d{1,8}(\.\d{1,2})?$/','price'=>'required|regex:/^\d{1,8}(\.\d{1,2})?$/','is_active'=>'required|boolean','notes'=>'nullable|string|max:2000']);
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
