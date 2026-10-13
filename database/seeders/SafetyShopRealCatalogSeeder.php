<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Modules\SafetyShop\Products\Models\Product;
use App\Modules\SafetyShop\Shared\Models\Master;
use Illuminate\Database\Seeder;

class SafetyShopRealCatalogSeeder extends Seeder
{
    public function run()
    {
        $company = Company::where('name', 'Harbour Edge Company Limited')->firstOrFail();
        $master = function ($type, $name, array $extra = []) use ($company) {
            return Master::updateOrCreate(['company_id'=>$company->id,'type'=>$type,'name'=>$name], $extra + ['is_active'=>true]);
        };

        $categories = [];
        foreach ([
            ['Head Protection','HEAD'], ['Eye & Face Protection','EYE'], ['Hearing Protection','HEAR'],
            ['Respiratory Protection','RESP'], ['Hand Protection','HAND'], ['Foot Protection','FOOT'],
            ['Protective Clothing','CLOTH'], ['Fall Protection','FALL'], ['Road & Site Safety','SITE'],
            ['Fire Safety','FIRE'],
        ] as [$name,$prefix]) $categories[$name] = $master('category',$name,['sku_prefix'=>$prefix]);

        foreach (['3M','Honeywell','MSA','Ansell','Uvex','Delta Plus','Portwest','Safety Jogger','JSP','Dräger'] as $name) $master('brand',$name);
        foreach (['Universal','S','M','L','XL','XXL','39','40','41','42','43','44','45','46'] as $name) $master('size',$name);
        foreach (['piece','pair','box','pack','roll','set','carton'] as $name) $master('unit',$name);
        foreach (['EN 397','ANSI Z89.1','EN 166','ANSI Z87.1','EN 352-1','EN 149 FFP2','EN 388','EN ISO 20345','EN ISO 20471','EN 361','EN 471','EN 3'] as $name) $master('safety_standard',$name);

        $master('supplier','Gulf Safety Equipment Trading',['contact_person'=>'Khalid Al-Mutairi','phone'=>'+966138123450','email'=>'sales@gulfsafety.test','vat_number'=>'310456789100003','commercial_registration'=>'2050456123','website'=>'https://gulfsafety.test','address'=>'King Abdulaziz Road, Al Jubail 35514']);
        $master('supplier','Eastern Industrial Tools Co.',['contact_person'=>'Noura Al-Shammari','phone'=>'+966138765421','email'=>'orders@eastern-tools.test','vat_number'=>'311567891200003','commercial_registration'=>'2050789456','website'=>'https://eastern-tools.test','address'=>'Support Industries Area, Jubail Industrial City 35717']);
        $master('supplier','Saudi PPE Solutions Ltd.',['contact_person'=>'Abdullah Al-Harbi','phone'=>'+966114567890','email'=>'supply@saudippe.test','vat_number'=>'312678912300003','commercial_registration'=>'1010897654','website'=>'https://saudippe.test','address'=>'Second Industrial City, Riyadh 14334']);

        $master('location','Main Safety Shop',['phone'=>'+966138000101','email'=>'shop@harbouredge.test','address'=>'Al Dana District, Jubail 35514']);
        $master('location','Central PPE Warehouse',['phone'=>'+966138000102','email'=>'warehouse@harbouredge.test','address'=>'Support Industries Area, Jubail Industrial City 35717']);
        $master('location','Project Site Store',['phone'=>'+966138000103','email'=>'site.store@harbouredge.test','address'=>'Jubail Industrial Project Area']);

        $products = [
            ['HEAD-0001','6290002000008','Vented Safety Helmet','Head Protection','MSA','Universal','piece','EN 397',20,3200,5500,'HDPE shell, six-point suspension and adjustable ratchet.'],
            ['HEAD-0002','6290002000015','Full Brim Safety Helmet','Head Protection','Honeywell','Universal','piece','ANSI Z89.1',12,4800,7800,'Full-brim industrial helmet for electrical and construction work.'],
            ['EYE-0001','6290002000022','Clear Anti-Fog Safety Spectacles','Eye & Face Protection','Uvex','Universal','piece','EN 166',30,1450,2600,'Wraparound polycarbonate lens with anti-fog and scratch-resistant coating.'],
            ['EYE-0002','6290002000039','Indirect Vent Chemical Goggles','Eye & Face Protection','3M','Universal','piece','ANSI Z87.1',15,2850,4700,'Sealed chemical splash goggles with indirect ventilation.'],
            ['HEAR-0001','6290002000046','Industrial Ear Muff 30 dB','Hearing Protection','3M','Universal','piece','EN 352-1',12,5200,8500,'Adjustable over-head hearing protector for high-noise areas.'],
            ['RESP-0001','6290002000053','FFP2 Disposable Respirator','Respiratory Protection','Dräger','Universal','box','EN 149 FFP2',10,6800,10500,'Box of 20 valved FFP2 particulate respirators.'],
            ['HAND-0001','6290002000060','Nitrile Coated Work Gloves','Hand Protection','Ansell','L','pair','EN 388',50,950,1800,'Abrasion-resistant nitrile palm gloves for material handling.'],
            ['HAND-0002','6290002000077','Chemical Resistant Nitrile Gloves','Hand Protection','Ansell','XL','pair','EN 388',24,2400,3900,'Long-cuff reusable nitrile gloves for oils and light chemicals.'],
            ['FOOT-0001','6290002000084','S3 Steel Toe Safety Shoes','Foot Protection','Safety Jogger','42','pair','EN ISO 20345',8,11800,16500,'Slip-resistant S3 leather safety shoes with steel toe and midsole.'],
            ['CLOTH-0001','6290002000091','High Visibility Reflective Vest','Protective Clothing','Portwest','XL','piece','EN ISO 20471',25,1250,2200,'Orange high-visibility vest with reflective tape and front closure.'],
            ['FALL-0001','6290002000107','Full Body Safety Harness','Fall Protection','Delta Plus','Universal','set','EN 361',6,14500,21500,'Two-point fall-arrest harness with dorsal and sternal attachment.'],
            ['SITE-0001','6290002000114','750 mm Traffic Cone with Reflective Collar','Road & Site Safety','JSP','Universal','piece','EN 471',20,4200,6800,'Heavy-duty PVC traffic cone with high-intensity reflective sleeve.'],
            ['FIRE-0001','6290002000121','6 kg ABC Dry Powder Extinguisher','Fire Safety','Portwest','Universal','piece','EN 3',6,13200,18500,'Rechargeable 6 kg ABC extinguisher with pressure gauge and wall bracket.'],
        ];
        foreach ($products as [$sku,$barcode,$name,$category,$brand,$size,$unit,$standard,$reorder,$cost,$price,$notes]) {
            Product::updateOrCreate(['company_id'=>$company->id,'sku'=>$sku],['barcode'=>$barcode,'name'=>$name,'category_id'=>$categories[$category]->id,'brand'=>$brand,'size'=>$size,'unit'=>$unit,'safety_standard'=>$standard,'reorder_level'=>$reorder,'cost_cents'=>$cost,'price_cents'=>$price,'notes'=>$notes,'is_active'=>true]);
        }
    }
}
