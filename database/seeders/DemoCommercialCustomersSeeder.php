<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\User;
use App\Modules\SafetyShop\Sales\Models\Customer;
use Illuminate\Database\Seeder;

class DemoCommercialCustomersSeeder extends Seeder
{
    public function run()
    {
        $company = Company::where('name', 'Harbour Edge Company Limited')->firstOrFail();
        $approver = User::where('company_id', $company->id)->whereIn('role', ['admin', 'manager'])->first();
        $rows = [
            ['name'=>'Najd Industrial Supplies Co.','contact_person'=>'Fahad Al-Qahtani','phone'=>'+966551234781','email'=>'purchasing@najd-industrial.test','vat_number'=>'310123456700003','commercial_registration'=>'1010456789','address'=>'Building 2145, King Fahd Road, Al Olaya District, Riyadh 12214','building_number'=>'2145','street'=>'King Fahd Road','district'=>'Al Olaya','city'=>'Riyadh','postal_code'=>'12214'],
            ['name'=>'Red Sea Technical Services Ltd.','contact_person'=>'Maha Al-Harbi','phone'=>'+966561234782','email'=>'procurement@redsea-technical.test','vat_number'=>'311234567800003','commercial_registration'=>'4030987654','address'=>'Building 7781, Prince Sultan Street, Al Zahra District, Jeddah 23425','building_number'=>'7781','street'=>'Prince Sultan Street','district'=>'Al Zahra','city'=>'Jeddah','postal_code'=>'23425'],
            ['name'=>'Eastern Gulf Contracting Est.','contact_person'=>'Omar Al-Dossary','phone'=>'+966571234783','email'=>'orders@easterngulf.test','vat_number'=>'312345678900003','commercial_registration'=>'2050123456','address'=>'Building 3320, King Abdulaziz Road, Al Dana District, Jubail 35514','building_number'=>'3320','street'=>'King Abdulaziz Road','district'=>'Al Dana','city'=>'Jubail','postal_code'=>'35514'],
        ];
        foreach ($rows as $row) {
            Customer::updateOrCreate(['company_id'=>$company->id, 'email'=>$row['email']], $row + ['customer_type'=>'company','country_code'=>'SA','approval_status'=>'approved','approved_by'=>optional($approver)->id,'approved_at'=>now(),'is_active'=>true]);
        }
    }
}
