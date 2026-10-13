<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('safety_shop_sales', function (Blueprint $table) {
            $table->string('invoice_notes', 1000)->nullable()->after('customer_address');
        });
    }

    public function down(): void
    {
        Schema::table('safety_shop_sales', function (Blueprint $table) {
            $table->dropColumn('invoice_notes');
        });
    }
};
