<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('safety_shop_sales', function (Blueprint $table) {
            $table->boolean('payment_received')->default(false)->after('payment_method');
        });

        DB::table('safety_shop_sales')
            ->whereColumn('paid_cents', '>=', 'total_cents')
            ->update(['payment_received' => true]);
    }

    public function down(): void
    {
        Schema::table('safety_shop_sales', function (Blueprint $table) {
            $table->dropColumn('payment_received');
        });
    }
};
