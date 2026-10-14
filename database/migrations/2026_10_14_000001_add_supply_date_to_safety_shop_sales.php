<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('safety_shop_sales', function (Blueprint $table) {
            $table->date('supply_date')->nullable()->after('invoice_number');
        });
        DB::table('safety_shop_sales')->whereNull('supply_date')->update(['supply_date' => DB::raw('DATE(created_at)')]);
    }

    public function down(): void
    {
        Schema::table('safety_shop_sales', function (Blueprint $table) {
            $table->dropColumn('supply_date');
        });
    }
};
