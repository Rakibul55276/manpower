<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('safety_shop_sales')) {
            DB::table('safety_shop_sales')->whereIn('customer', ['Walk-in customer', 'Walk-in Customer'])->update(['customer' => 'Retail Customer']);
        }

        if (Schema::hasTable('invoice_customers')) {
            DB::table('invoice_customers')->where('name', 'Walk-in Customer')->update(['name' => 'Retail Customer', 'name_ar' => 'عميل تجزئة']);
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('safety_shop_sales')) {
            DB::table('safety_shop_sales')->where('customer', 'Retail Customer')->whereNull('customer_id')->update(['customer' => 'Walk-in customer']);
        }

        if (Schema::hasTable('invoice_customers')) {
            DB::table('invoice_customers')->where('name', 'Retail Customer')->update(['name' => 'Walk-in Customer', 'name_ar' => 'عميل نقدي']);
        }
    }
};
