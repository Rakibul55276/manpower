<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSupplierCompanyFields extends Migration
{
    public function up()
    {
        Schema::table('safety_shop_masters', function (Blueprint $table) {
            $table->string('contact_person', 150)->nullable()->after('name');
            $table->string('vat_number', 15)->nullable()->after('email');
            $table->string('commercial_registration', 30)->nullable()->after('vat_number');
            $table->string('website', 150)->nullable()->after('commercial_registration');
        });
    }

    public function down()
    {
        Schema::table('safety_shop_masters', function (Blueprint $table) {
            $table->dropColumn(['contact_person', 'vat_number', 'commercial_registration', 'website']);
        });
    }
}
