<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ScopeProductIdentifiersToCompany extends Migration
{
    public function up()
    {
        Schema::table('safety_shop_products', function (Blueprint $table) {
            $table->dropUnique('safety_shop_products_sku_unique');
            $table->dropUnique('safety_shop_products_barcode_unique');
            $table->unique(['company_id', 'sku'], 'ss_products_company_sku_unique');
            $table->unique(['company_id', 'barcode'], 'ss_products_company_barcode_unique');
        });
    }

    public function down()
    {
        Schema::table('safety_shop_products', function (Blueprint $table) {
            $table->dropUnique('ss_products_company_sku_unique');
            $table->dropUnique('ss_products_company_barcode_unique');
            $table->unique('sku');
            $table->unique('barcode');
        });
    }
}
