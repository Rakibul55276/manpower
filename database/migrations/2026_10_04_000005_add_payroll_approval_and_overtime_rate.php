<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
class AddPayrollApprovalAndOvertimeRate extends Migration
{
    public function up()
    {
        Schema::table('payrolls', function (Blueprint $table) {
            $table->foreignId('approved_by')->nullable()->after('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('approved_at')->nullable()->after('approved_by');
        });
        Schema::table('employees', function (Blueprint $table) { $table->unsignedInteger('overtime_rate_cents')->default(0)->after('hourly_rate_cents'); });
        Schema::table('timesheets', function (Blueprint $table) { $table->unsignedInteger('overtime_rate_cents')->default(0)->after('hourly_rate_cents'); });
        DB::table('payrolls')->where('status', 'draft')->update(['status' => 'pending']);
        DB::table('employees')->where('employment_type', 'own')->update(['overtime_rate_cents' => DB::raw('hourly_rate_cents')]);
        DB::table('timesheets')->whereIn('employee_id', DB::table('employees')->where('employment_type', 'own')->select('id'))->update(['overtime_rate_cents' => DB::raw('hourly_rate_cents')]);
    }
    public function down()
    {
        DB::table('payrolls')->where('status', 'pending')->orWhere('status', 'approved')->update(['status' => 'draft']);
        Schema::table('payrolls', function (Blueprint $table) { $table->dropConstrainedForeignId('approved_by'); $table->dropColumn('approved_at'); });
        Schema::table('employees', function (Blueprint $table) { $table->dropColumn('overtime_rate_cents'); });
        Schema::table('timesheets', function (Blueprint $table) { $table->dropColumn('overtime_rate_cents'); });
    }
}
