<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void { Schema::table('safety_shop_receipt_settings',function(Blueprint $table){$table->string('company_name_ar',180)->nullable()->after('company_name');$table->string('tagline_ar',180)->nullable()->after('tagline');$table->string('address_ar',500)->nullable()->after('address');$table->string('city_ar',100)->nullable()->after('city');}); }
    public function down(): void { Schema::table('safety_shop_receipt_settings',function(Blueprint $table){$table->dropColumn(['company_name_ar','tagline_ar','address_ar','city_ar']);}); }
};
