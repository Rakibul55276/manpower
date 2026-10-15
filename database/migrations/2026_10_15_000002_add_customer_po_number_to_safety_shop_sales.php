<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('safety_shop_sales', function (Blueprint $table) {
            $table->string('customer_po_number', 100)->nullable()->after('quotation_id')->index();
        });
    }

    public function down(): void
    {
        Schema::table('safety_shop_sales', function (Blueprint $table) {
            $table->dropIndex(['customer_po_number']);
            $table->dropColumn('customer_po_number');
        });
    }
};
