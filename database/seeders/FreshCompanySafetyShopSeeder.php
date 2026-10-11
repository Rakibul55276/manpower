<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class FreshCompanySafetyShopSeeder extends Seeder
{
    public function run()
    {
        DB::transaction(function () {
            DB::table('safety_shop_return_lines')->delete();
            DB::table('safety_shop_returns')->delete();
            DB::table('safety_shop_sale_lines')->delete();
            DB::table('safety_shop_sales')->delete();
            DB::table('safety_shop_movements')->delete();
            DB::table('safety_shop_stocks')->delete();
            DB::table('safety_shop_customers')->delete();
            DB::table('safety_shop_products')->delete();
            DB::table('safety_shop_masters')->delete();

            Company::where('is_active', true)->orderBy('id')->each(function ($company) {
                $now = now();
                $adminId = User::where('company_id',$company->id)->where('role','admin')->value('id');
                $locationId = DB::table('safety_shop_masters')->insertGetId([
                    'company_id'=>$company->id,'type'=>'location','name'=>'Main Shop','is_active'=>1,'created_at'=>$now,'updated_at'=>$now,
                ]);
                $warehouseId = DB::table('safety_shop_masters')->insertGetId([
                    'company_id'=>$company->id,'type'=>'location','name'=>'Main Warehouse','is_active'=>1,'created_at'=>$now,'updated_at'=>$now,
                ]);
                $supplierId = DB::table('safety_shop_masters')->insertGetId([
                    'company_id'=>$company->id,'type'=>'supplier','name'=>'Demo Safety Supplier','contact_person'=>'Sales Desk','phone'=>'+966500000000','email'=>'supplier@example.test','address'=>'Riyadh','is_active'=>1,'created_at'=>$now,'updated_at'=>$now,
                ]);
                $categoryId = DB::table('safety_shop_masters')->insertGetId([
                    'company_id'=>$company->id,'type'=>'category','name'=>'Head Protection','sku_prefix'=>'HP','is_active'=>1,'created_at'=>$now,'updated_at'=>$now,
                ]);
                foreach (['3M','Honeywell'] as $name) DB::table('safety_shop_masters')->insert(['company_id'=>$company->id,'type'=>'brand','name'=>$name,'is_active'=>1,'created_at'=>$now,'updated_at'=>$now]);
                foreach (['piece','box'] as $name) DB::table('safety_shop_masters')->insert(['company_id'=>$company->id,'type'=>'unit','name'=>$name,'is_active'=>1,'created_at'=>$now,'updated_at'=>$now]);

                $suffix=str_pad((string)$company->id,4,'0',STR_PAD_LEFT);
                $products=[
                    ['sku'=>'HP-0001','barcode'=>'629'. $suffix .'000001','name'=>'Industrial Safety Helmet','brand'=>'3M','cost_cents'=>2850,'price_cents'=>4500,'quantity'=>25],
                    ['sku'=>'HP-0002','barcode'=>'629'. $suffix .'000002','name'=>'Vented Safety Helmet','brand'=>'Honeywell','cost_cents'=>3400,'price_cents'=>5500,'quantity'=>15],
                ];
                foreach ($products as $item) {
                    $productId=DB::table('safety_shop_products')->insertGetId([
                        'company_id'=>$company->id,'sku'=>$item['sku'],'barcode'=>$item['barcode'],'name'=>$item['name'],'category_id'=>$categoryId,'brand'=>$item['brand'],'unit'=>'piece','safety_standard'=>'EN 397','reorder_level'=>5,'cost_cents'=>$item['cost_cents'],'price_cents'=>$item['price_cents'],'is_active'=>1,'notes'=>'Fresh company-scoped demo data','created_at'=>$now,'updated_at'=>$now,
                    ]);
                    DB::table('safety_shop_stocks')->insert(['product_id'=>$productId,'location_id'=>$warehouseId,'quantity'=>$item['quantity'],'created_at'=>$now,'updated_at'=>$now]);
                    if ($adminId) DB::table('safety_shop_movements')->insert(['company_id'=>$company->id,'request_key'=>(string)Str::uuid(),'type'=>'receipt','product_id'=>$productId,'location_id'=>$warehouseId,'destination_id'=>null,'supplier_id'=>$supplierId,'quantity'=>$item['quantity'],'balance_after'=>$item['quantity'],'destination_balance_after'=>null,'movement_date'=>$now->toDateString(),'reference'=>'OPENING-'.$company->id.'-'.$productId,'recipient'=>null,'notes'=>'Fresh opening stock after tenant reset','created_by'=>$adminId,'created_at'=>$now,'updated_at'=>$now]);
                }
                DB::table('safety_shop_customers')->insert([
                    'company_id'=>$company->id,'customer_type'=>'company','name'=>$company->name.' Demo Customer','contact_person'=>'Purchasing Desk','phone'=>'+96655'.str_pad((string)$company->id,7,'0',STR_PAD_LEFT),'email'=>'customer'.$company->id.'@example.test','country_code'=>'SA','purchase_count'=>0,'lifetime_value_cents'=>0,'is_active'=>1,'approval_status'=>'approved','approved_by'=>$adminId,'approved_at'=>$adminId?$now:null,'created_at'=>$now,'updated_at'=>$now,
                ]);
            });
        });
    }
}
