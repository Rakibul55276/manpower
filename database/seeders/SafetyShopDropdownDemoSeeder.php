<?php

namespace Database\Seeders;

use App\Modules\SafetyShop\Products\Models\Product;
use App\Modules\SafetyShop\Shared\Models\Master;
use Illuminate\Database\Seeder;
use App\Models\Company;

class SafetyShopDropdownDemoSeeder extends Seeder
{
    public function run()
    {
        $company=Company::where('name','Manpower Operations')->firstOrFail();
        $masters = [
            'category' => ['Head Protection', 'Hand Protection', 'Foot Protection', 'Eye Protection', 'Workwear'],
            'brand' => ['3M', 'Ansell', 'Honeywell', 'Uvex', 'Portwest'],
            'size' => ['Small', 'Medium', 'Large', 'XL', 'XXL'],
            'unit' => ['piece', 'pair', 'box', 'pack', 'carton'],
            'safety_standard' => ['EN 397', 'EN 388', 'EN ISO 20345', 'EN 166', 'ANSI Z89.1'],
        ];

        $ids = [];
        foreach ($masters as $type => $names) {
            foreach ($names as $name) {
                $prefixes=['Head Protection'=>'HP','Hand Protection'=>'HAND','Foot Protection'=>'FP','Eye Protection'=>'EP','Workwear'=>'WW'];
                $record = Master::firstOrCreate(['company_id'=>$company->id, 'type'=>$type, 'name'=>$name], ['is_active'=>true,'sku_prefix'=>$type==='category'?$prefixes[$name]:null]);
                if (!$record->is_active) $record->update(['is_active'=>true]);
                $ids[$type][$name] = $record->id;
            }
        }

        $suppliers = [
            ['name'=>'Riyadh Safety Supplies Co.','contact_person'=>'Fahad Al-Qahtani','phone'=>'+966 11 245 8800','email'=>'sales@riyadh-safety.example','vat_number'=>'310000000000003','commercial_registration'=>'1010456789','website'=>'https://riyadh-safety.example','address'=>'Second Industrial City, Riyadh'],
            ['name'=>'Eastern PPE Trading','contact_person'=>'Noura Al-Harbi','phone'=>'+966 13 812 4400','email'=>'orders@eastern-ppe.example','vat_number'=>null,'commercial_registration'=>'2050123456','website'=>null,'address'=>'Industrial Area, Dammam'],
        ];
        foreach ($suppliers as $supplier) {
            Master::updateOrCreate(
                ['company_id'=>$company->id,'type'=>'supplier','name'=>$supplier['name']],
                $supplier+['company_id'=>$company->id,'type'=>'supplier','is_active'=>true]
            );
        }

        $products = [
            ['TEST-PPE-001','6291100000011','Industrial Safety Helmet','Head Protection','3M','Medium','piece','EN 397',10,2850,4500],
            ['TEST-PPE-002','6291100000028','Vented Safety Helmet','Head Protection','Honeywell','Large','piece','ANSI Z89.1',8,3400,5500],
            ['TEST-PPE-003','6291100000035','Cut Resistant Gloves','Hand Protection','Ansell','Medium','pair','EN 388',20,1250,2200],
            ['TEST-PPE-004','6291100000042','Chemical Resistant Gloves','Hand Protection','Honeywell','Large','pair','EN 388',15,1800,3200],
            ['TEST-PPE-005','6291100000059','Steel Toe Safety Shoes','Foot Protection','Portwest','XL','pair','EN ISO 20345',6,9500,14500],
            ['TEST-PPE-006','6291100000066','Clear Safety Goggles','Eye Protection','Uvex','Medium','piece','EN 166',25,1600,2900],
            ['TEST-PPE-007','6291100000073','Anti-Fog Safety Glasses','Eye Protection','3M','Small','piece','EN 166',25,2200,3800],
            ['TEST-PPE-008','6291100000080','High Visibility Vest','Workwear','Portwest','XL','piece','ANSI Z89.1',12,2100,3500],
            ['TEST-PPE-009','6291100000097','Disposable Ear Plugs Box','Head Protection','3M','Large','box','ANSI Z89.1',5,4800,7200],
            ['TEST-PPE-010','6291100000103','General Purpose Gloves Pack','Hand Protection','Ansell','XXL','pack','EN 388',10,3600,5900],
        ];

        foreach ($products as [$sku,$barcode,$name,$category,$brand,$size,$unit,$standard,$reorder,$cost,$price]) {
            Product::updateOrCreate(['company_id'=>$company->id,'sku'=>$sku], [
                'company_id'=>$company->id,'barcode'=>$barcode, 'name'=>$name, 'category_id'=>$ids['category'][$category],
                'brand'=>$brand, 'size'=>$size, 'unit'=>$unit, 'safety_standard'=>$standard,
                'reorder_level'=>$reorder, 'cost_cents'=>$cost, 'price_cents'=>$price,
                'is_active'=>true, 'notes'=>'TEST DATA — safe to remove after dropdown verification.',
            ]);
        }
    }
}
