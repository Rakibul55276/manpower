<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ScopeSafetyShopMasterNamesToCompanies extends Migration
{
    public function up()
    {
        Schema::table('safety_shop_masters', function (Blueprint $table) {
            $table->dropUnique('safety_shop_masters_type_name_unique');
            $table->unique(['company_id','type','name'], 'ss_masters_company_type_name_unique');
        });
    }

    public function down()
    {
        Schema::table('safety_shop_masters', function (Blueprint $table) {
            $table->dropUnique('ss_masters_company_type_name_unique');
            $table->unique(['type','name']);
        });
    }
}
