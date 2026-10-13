<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddInvoiceNumberToSafetyShopSales extends Migration
{
    public function up()
    {
        Schema::table('safety_shop_sales', function (Blueprint $table) { $table->string('invoice_number', 20)->nullable()->after('id'); });
        DB::table('safety_shop_sales')->orderBy('id')->each(function ($sale) {
            $token = strtoupper(substr(hash('sha256', ($sale->request_key ?: 'sale').'-'.$sale->id), 0, 8));
            DB::table('safety_shop_sales')->where('id', $sale->id)->update(['invoice_number' => 'INV-'.$token]);
        });
        Schema::table('safety_shop_sales', function (Blueprint $table) { $table->unique('invoice_number'); });
    }

    public function down()
    {
        Schema::table('safety_shop_sales', function (Blueprint $table) { $table->dropUnique(['invoice_number']); $table->dropColumn('invoice_number'); });
    }
}
