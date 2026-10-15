<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddZatcaAdditionalNumberToCustomers extends Migration
{
    public function up(): void
    {
        Schema::table('safety_shop_customers', function (Blueprint $table) {
            $table->string('additional_number', 4)->nullable()->after('building_number');
        });
    }

    public function down(): void
    {
        Schema::table('safety_shop_customers', fn (Blueprint $table) => $table->dropColumn('additional_number'));
    }
}
