<?php
namespace App\Modules\SafetyShop\Products\Services;
use App\Modules\SafetyShop\Products\Models\Product;
use App\Modules\SafetyShop\Stock\Models\Stock;
use Illuminate\Http\Request;
class CatalogQuery
{
    public function build(Request $r)
    {
        $r->validate(['search'=>'nullable|string|max:150','category'=>'nullable|integer','status'=>'nullable|in:active,inactive','low'=>'nullable|in:1']);
        $q = Product::with('category')->select('safety_shop_products.*')->selectSub(Stock::selectRaw('COALESCE(SUM(quantity),0)')->whereColumn('product_id','safety_shop_products.id'), 'stock_total');
        if ($r->filled('search')) $q->where(function ($q) use ($r) { $q->where('barcode','like','%'.$r->search.'%')->orWhere('sku','like','%'.$r->search.'%')->orWhere('name','like','%'.$r->search.'%')->orWhere('brand','like','%'.$r->search.'%'); });
        if ($r->filled('category')) $q->where('category_id',$r->category);
        if ($r->filled('status')) $q->where('is_active',$r->status === 'active');
        if ($r->low) $q->whereRaw('(SELECT COALESCE(SUM(quantity),0) FROM safety_shop_stocks WHERE product_id = safety_shop_products.id) <= reorder_level')->where('is_active',true);
        return $q;
    }
}
