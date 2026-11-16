<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('safety_shop_receipt_settings', 'quotation_valid_days')) {
            Schema::table('safety_shop_receipt_settings', fn (Blueprint $table) => $table->unsignedSmallInteger('quotation_valid_days')->default(30)->after('default_location_id'));
        }
        if (!Schema::hasColumn('safety_shop_receipt_settings', 'quotation_terms')) {
            Schema::table('safety_shop_receipt_settings', fn (Blueprint $table) => $table->text('quotation_terms')->nullable()->after('quotation_valid_days'));
        }
    }

    public function down(): void
    {
        $columns=array_values(array_filter(['quotation_valid_days','quotation_terms'],fn($column)=>Schema::hasColumn('safety_shop_receipt_settings',$column)));
        if($columns)Schema::table('safety_shop_receipt_settings',fn(Blueprint $table)=>$table->dropColumn($columns));
    }
};
