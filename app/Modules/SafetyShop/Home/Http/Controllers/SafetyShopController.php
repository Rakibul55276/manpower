<?php
namespace App\Modules\SafetyShop\Home\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\SafetyShop\Products\Models\Product;
use App\Modules\SafetyShop\Sales\Models\Customer;
use App\Modules\SafetyShop\Sales\Models\Sale;
use App\Modules\SafetyShop\Shared\Models\Master;
use App\Modules\SafetyShop\Stock\Models\Stock;
use Illuminate\Http\Request;

class SafetyShopController extends Controller
{
    public function index(Request $request)
    {
        if($request->hasAny(['search','category','status','low'])) return app(\App\Modules\SafetyShop\Products\Http\Controllers\ProductController::class)->index($request);
        $today=now()->startOfDay();
        $month=now()->startOfMonth();
        $metrics=[
            'sales_today'=>Sale::where('created_at','>=',$today)->count(),
            'revenue_today'=>Sale::where('created_at','>=',$today)->sum('total_cents'),
            'revenue_month'=>Sale::where('created_at','>=',$month)->sum('total_cents'),
            'customers'=>Customer::where('is_active',true)->count(),
            'company_customers'=>Customer::where('is_active',true)->where('customer_type','company')->count(),
            'products'=>Product::where('is_active',true)->count(),
            'units'=>Stock::sum('quantity'),
            'low'=>Product::where('is_active',true)->whereRaw('(SELECT COALESCE(SUM(quantity),0) FROM safety_shop_stocks WHERE product_id = safety_shop_products.id) <= reorder_level')->count(),
            'locations'=>Master::where('type','location')->where('is_active',true)->count(),
        ];
        $recentSales=Sale::with(['location','creator'])->latest('id')->limit(8)->get();
        $lowProducts=Product::where('is_active',true)->whereRaw('(SELECT COALESCE(SUM(quantity),0) FROM safety_shop_stocks WHERE product_id = safety_shop_products.id) <= reorder_level')->withSum('stocks as stock_total','quantity')->orderBy('stock_total')->limit(8)->get();
        return view('safety-shop.home.index',compact('metrics','recentSales','lowProducts'));
    }
}
