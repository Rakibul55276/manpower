<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateCompanyBranchesAndScopeUsers extends Migration
{
    public function up()
    {
        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->restrictOnDelete();
            $table->string('name', 150);
            $table->string('code', 50);
            $table->string('location', 150);
            $table->string('contact_person', 150)->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('email', 150)->nullable();
            $table->string('address', 500)->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['company_id', 'name']);
            $table->unique(['company_id', 'code']);
            $table->index(['company_id', 'is_active']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->after('is_active')->constrained()->restrictOnDelete();
            $table->foreignId('branch_id')->nullable()->after('company_id')->constrained()->restrictOnDelete();
            $table->index(['role', 'company_id', 'branch_id']);
        });
        Schema::table('employees', function (Blueprint $table) {
            $table->foreignId('branch_id')->nullable()->after('company_id')->constrained()->restrictOnDelete();
            $table->index(['branch_id', 'status']);
        });
        Schema::table('payrolls', function (Blueprint $table) {
            $table->foreignId('branch_id')->nullable()->after('company_id')->constrained()->restrictOnDelete();
            $table->index(['branch_id', 'month']);
        });
        Schema::table('timesheets', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->after('employee_id')->constrained()->restrictOnDelete();
            $table->foreignId('branch_id')->nullable()->after('company_id')->constrained()->restrictOnDelete();
            $table->index(['branch_id', 'work_date']);
        });

        // Preserve existing records under an explicit per-company branch. Ambiguous
        // multi-company manager assignments are disabled for deliberate reassignment.
        DB::table('users')->where('role', 'admin')->update(['role' => 'super_admin']);
        DB::table('companies')->orderBy('id')->each(function ($company) {
            $branchId = DB::table('branches')->insertGetId([
                'company_id'=>$company->id, 'name'=>'Main Branch', 'code'=>'MAIN',
                'location'=>$company->location ?: 'Main location', 'is_active'=>true,
                'created_at'=>now(), 'updated_at'=>now(),
            ]);
            DB::table('employees')->where('company_id',$company->id)->update(['branch_id'=>$branchId]);
            DB::table('payrolls')->where('company_id',$company->id)->update(['branch_id'=>$branchId]);
            DB::table('timesheets')->whereIn('employee_id',DB::table('employees')->where('company_id',$company->id)->select('id'))->update(['company_id'=>$company->id,'branch_id'=>$branchId]);
            $managerIds=DB::table('company_user')->where('company_id',$company->id)->pluck('user_id');
            foreach($managerIds as $managerId){
                if(DB::table('company_user')->where('user_id',$managerId)->count()===1) DB::table('users')->where('id',$managerId)->where('role','manager')->update(['company_id'=>$company->id,'branch_id'=>$branchId]);
                else DB::table('users')->where('id',$managerId)->where('role','manager')->update(['is_active'=>false]);
            }
        });
    }

    public function down()
    {
        Schema::table('timesheets', function (Blueprint $table) {
            $table->dropIndex(['branch_id', 'work_date']);
            $table->dropForeign(['branch_id']);
            $table->dropForeign(['company_id']);
            $table->dropColumn(['company_id','branch_id']);
        });
        Schema::table('payrolls', function (Blueprint $table) {
            $table->dropForeign(['branch_id']);
            $table->dropIndex(['branch_id', 'month']);
            $table->dropColumn('branch_id');
        });
        Schema::table('employees', function (Blueprint $table) {
            $table->dropForeign(['branch_id']);
            $table->dropIndex(['branch_id', 'status']);
            $table->dropColumn('branch_id');
        });
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['role', 'company_id', 'branch_id']);
            $table->dropForeign(['branch_id']);
            $table->dropForeign(['company_id']);
            $table->dropColumn(['company_id', 'branch_id']);
        });
        Schema::dropIfExists('branches');
    }
}
