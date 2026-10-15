<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('safety_shop_receipt_settings',function(Blueprint $table){
            $table->string('building_number',4)->nullable()->after('address_ar');
            $table->string('additional_number',4)->nullable()->after('building_number');
            $table->string('street',150)->nullable()->after('additional_number');
            $table->string('district',100)->nullable()->after('street');
            $table->string('country_code',2)->default('SA')->after('postal_code');
        });
    }
    public function down(): void
    {
        Schema::table('safety_shop_receipt_settings',fn(Blueprint $table)=>$table->dropColumn(['building_number','additional_number','street','district','country_code']));
    }
};
