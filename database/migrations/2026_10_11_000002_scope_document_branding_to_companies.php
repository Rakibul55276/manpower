<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ScopeDocumentBrandingToCompanies extends Migration
{
    public function up()
    {
        Schema::table('safety_shop_receipt_settings', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->after('id')->constrained('companies')->cascadeOnDelete();
        });

        $template = DB::table('safety_shop_receipt_settings')->orderBy('id')->first();
        DB::table('safety_shop_receipt_settings')->update(['default_location_id'=>null]);
        $companies = DB::table('companies')->orderBy('id')->get();
        foreach ($companies as $index => $company) {
            if ($index === 0 && $template) {
                DB::table('safety_shop_receipt_settings')->where('id', $template->id)->update(['company_id'=>$company->id]);
                continue;
            }
            $values = $template ? (array)$template : [];
            unset($values['id']);
            $values['company_id']=$company->id;
            $values['company_name']=$company->name;
            $values['default_location_id']=null;
            $values['created_at']=now(); $values['updated_at']=now();
            DB::table('safety_shop_receipt_settings')->insert($values);
        }

        Schema::table('safety_shop_receipt_settings', function (Blueprint $table) {
            $table->unique('company_id');
        });
    }

    public function down()
    {
        Schema::table('safety_shop_receipt_settings', function (Blueprint $table) {
            $table->dropUnique(['company_id']);
            $table->dropForeign(['company_id']);
            $table->dropColumn('company_id');
        });
    }
}
