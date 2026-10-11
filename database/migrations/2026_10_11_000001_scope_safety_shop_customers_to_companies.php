<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ScopeSafetyShopCustomersToCompanies extends Migration
{
    public function up()
    {
        Schema::table('safety_shop_customers', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->after('id')->constrained()->restrictOnDelete();
        });

        DB::table('safety_shop_customers')->orderBy('id')->each(function ($customer) {
            $companyId = DB::table('safety_shop_sales')
                ->join('safety_shop_masters', 'safety_shop_masters.id', '=', 'safety_shop_sales.location_id')
                ->where('safety_shop_sales.customer_id', $customer->id)
                ->whereNotNull('safety_shop_masters.company_id')
                ->value('safety_shop_masters.company_id');
            $companyId = $companyId ?: DB::table('companies')->orderBy('id')->value('id');
            if ($companyId) DB::table('safety_shop_customers')->where('id', $customer->id)->update(['company_id' => $companyId]);
        });

        Schema::table('safety_shop_customers', function (Blueprint $table) {
            $table->dropUnique('safety_shop_customers_phone_unique');
            $table->unique(['company_id', 'phone'], 'ss_customers_company_phone_unique');
            $table->index(['company_id', 'is_active'], 'ss_customers_company_active_index');
        });
    }

    public function down()
    {
        Schema::table('safety_shop_customers', function (Blueprint $table) {
            $table->dropIndex('ss_customers_company_active_index');
            $table->dropUnique('ss_customers_company_phone_unique');
            $table->unique('phone');
            $table->dropForeign(['company_id']);
            $table->dropColumn('company_id');
        });
    }
}
