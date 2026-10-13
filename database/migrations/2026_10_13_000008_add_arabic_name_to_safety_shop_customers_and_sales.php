<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('safety_shop_customers', function (Blueprint $table) {
            $table->string('name_ar', 150)->nullable()->after('name');
        });
        Schema::table('safety_shop_sales', function (Blueprint $table) {
            $table->string('customer_name_ar', 150)->nullable()->after('customer');
        });
    }

    public function down(): void
    {
        Schema::table('safety_shop_sales', function (Blueprint $table) {
            $table->dropColumn('customer_name_ar');
        });
        Schema::table('safety_shop_customers', function (Blueprint $table) {
            $table->dropColumn('name_ar');
        });
    }
};
