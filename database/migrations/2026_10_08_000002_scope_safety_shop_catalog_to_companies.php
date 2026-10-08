<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ScopeSafetyShopCatalogToCompanies extends Migration
{
    public function up()
    {
        Schema::table('safety_shop_masters', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->after('id')->constrained()->restrictOnDelete();
            $table->index(['company_id','type','is_active']);
        });
        Schema::table('safety_shop_products', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->after('id')->constrained()->restrictOnDelete();
            $table->index(['company_id','is_active']);
        });

        // The pre-tenant installation has one owning company. Existing catalog
        // rows are attached only when that ownership is unambiguous.
        $companies=DB::table('companies')->pluck('id');
        if($companies->count()===1){
            DB::table('safety_shop_masters')->whereNull('company_id')->update(['company_id'=>$companies->first()]);
            DB::table('safety_shop_products')->whereNull('company_id')->update(['company_id'=>$companies->first()]);
        }
    }

    public function down()
    {
        Schema::table('safety_shop_products', function (Blueprint $table) {$table->dropIndex(['company_id','is_active']);$table->dropForeign(['company_id']);$table->dropColumn('company_id');});
        Schema::table('safety_shop_masters', function (Blueprint $table) {$table->dropIndex(['company_id','type','is_active']);$table->dropForeign(['company_id']);$table->dropColumn('company_id');});
    }
}
