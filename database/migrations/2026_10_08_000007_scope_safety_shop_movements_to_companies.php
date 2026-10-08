<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ScopeSafetyShopMovementsToCompanies extends Migration
{
    public function up()
    {
        Schema::table('safety_shop_movements', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->after('id')->constrained()->restrictOnDelete();
            $table->index(['company_id','movement_date'], 'ss_movements_company_date_index');
        });

        DB::table('safety_shop_movements')->orderBy('id')->chunkById(200,function($movements){
            foreach($movements as $movement){
                $companyId=DB::table('safety_shop_products')->where('id',$movement->product_id)->value('company_id');
                if($companyId)DB::table('safety_shop_movements')->where('id',$movement->id)->update(['company_id'=>$companyId]);
            }
        });
    }

    public function down()
    {
        Schema::table('safety_shop_movements', function (Blueprint $table) {
            $table->dropIndex('ss_movements_company_date_index');
            $table->dropForeign(['company_id']);
            $table->dropColumn('company_id');
        });
    }
}
