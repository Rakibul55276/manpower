<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddCustomerTypesAndHarbourEdgeBranding extends Migration
{
    public function up()
    {
        Schema::table('safety_shop_customers',function(Blueprint $table){
            $table->string('customer_type',20)->default('retail')->after('id')->index();
            $table->string('contact_person',150)->nullable()->after('name');
            $table->string('vat_number',15)->nullable()->after('email');
            $table->string('commercial_registration',30)->nullable()->after('vat_number');
            $table->string('building_number',20)->nullable()->after('address');
            $table->string('street',150)->nullable()->after('building_number');
            $table->string('district',100)->nullable()->after('street');
            $table->string('city',100)->nullable()->after('district');
            $table->string('postal_code',10)->nullable()->after('city');
            $table->string('country_code',2)->default('SA')->after('postal_code');
        });
        Schema::table('safety_shop_sales',function(Blueprint $table){
            $table->string('customer_type',20)->default('retail')->after('customer_id');
            $table->string('customer_contact_person',150)->nullable()->after('customer');
            $table->string('customer_vat_number',15)->nullable()->after('customer_email');
            $table->string('customer_commercial_registration',30)->nullable()->after('customer_vat_number');
        });
        DB::table('safety_shop_receipt_settings')->update([
            'company_name'=>'Harbour Edge Company',
            'tagline'=>'Safety equipment, contracting, manpower supply and general trading',
            'logo_path'=>'public:images/harbour-edge-logo.png',
            'vat_number'=>'314482276100003',
            'commercial_registration'=>'7052965014',
            'phone'=>'0115646544',
            'email'=>'harbouredge368@gmail.com',
            'address'=>'Building 7830, Al Usul Street, Al Safat District',
            'city'=>'Al Jubail',
            'postal_code'=>'35514',
            'footer_text'=>'Thank you for choosing Harbour Edge Company.',
            'updated_at'=>now(),
        ]);
    }
    public function down()
    {
        Schema::table('safety_shop_sales',function(Blueprint $table){$table->dropColumn(['customer_type','customer_contact_person','customer_vat_number','customer_commercial_registration']);});
        Schema::table('safety_shop_customers',function(Blueprint $table){$table->dropColumn(['customer_type','contact_person','vat_number','commercial_registration','building_number','street','district','city','postal_code','country_code']);});
    }
}
