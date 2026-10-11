<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ScopeDesignationsToCompanies extends Migration
{
    public function up()
    {
        Schema::table('designations', function (Blueprint $table) {
            $table->dropUnique(['name']);
            $table->foreignId('company_id')->nullable()->after('id')->constrained('companies')->cascadeOnDelete();
        });

        $companies=DB::table('companies')->orderBy('id')->pluck('id');
        $designations=DB::table('designations')->orderBy('id')->get();
        foreach ($companies as $companyId) {
            foreach ($designations as $designation) {
                $used=DB::table('employees')->where('company_id',$companyId)->where('designation_id',$designation->id)->exists();
                if (!$used) continue;
                if ($designation->company_id === null && !DB::table('designations')->where('id',$designation->id)->whereNotNull('company_id')->exists()) {
                    DB::table('designations')->where('id',$designation->id)->update(['company_id'=>$companyId]);
                    $designation->company_id=$companyId;
                    continue;
                }
                if ((int)$designation->company_id === (int)$companyId) continue;
                $newId=DB::table('designations')->insertGetId(['company_id'=>$companyId,'name'=>$designation->name,'is_active'=>$designation->is_active,'created_at'=>$designation->created_at,'updated_at'=>$designation->updated_at]);
                DB::table('employees')->where('company_id',$companyId)->where('designation_id',$designation->id)->update(['designation_id'=>$newId]);
            }
        }
        DB::table('designations')->whereNull('company_id')->delete();

        Schema::table('designations', function (Blueprint $table) {
            $table->unique(['company_id','name']);
        });
    }

    public function down()
    {
        Schema::table('designations', function (Blueprint $table) {
            $table->dropUnique(['company_id','name']);
            $table->dropForeign(['company_id']);
            $table->dropColumn('company_id');
            $table->unique('name');
        });
    }
}
