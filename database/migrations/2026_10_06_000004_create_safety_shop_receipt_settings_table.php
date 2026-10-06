<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
class CreateSafetyShopReceiptSettingsTable extends Migration
{
    public function up()
    {
        Schema::create('safety_shop_receipt_settings',function(Blueprint $table){
            $table->id(); $table->string('company_name',180)->default('Safety Shop');
            $table->string('tagline',180)->nullable(); $table->string('logo_path')->nullable();
            $table->string('vat_number',30)->nullable(); $table->string('commercial_registration',30)->nullable();
            $table->string('phone',50)->nullable(); $table->string('email',150)->nullable();
            $table->string('website',150)->nullable(); $table->string('address',500)->nullable();
            $table->string('city',100)->nullable(); $table->string('postal_code',20)->nullable();
            $table->string('footer_text',500)->nullable(); $table->timestamps();
        });
        DB::table('safety_shop_receipt_settings')->insert(['company_name'=>'Safety Shop','tagline'=>'Manpower · Workforce Management','footer_text'=>'Thank you for your business.','created_at'=>now(),'updated_at'=>now()]);
    }
    public function down(){Schema::dropIfExists('safety_shop_receipt_settings');}
}
