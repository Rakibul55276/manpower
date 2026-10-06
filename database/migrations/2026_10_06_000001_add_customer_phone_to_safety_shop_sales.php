<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCustomerPhoneToSafetyShopSales extends Migration
{
    public function up()
    {
        Schema::table('safety_shop_sales', function (Blueprint $table) {
            $table->string('customer_phone', 30)->nullable()->after('customer');
        });
    }

    public function down()
    {
        Schema::table('safety_shop_sales', function (Blueprint $table) {
            $table->dropColumn('customer_phone');
        });
    }
}
