<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddHighVolumeQueryIndexes extends Migration
{
    public function up()
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->index(['employment_type', 'company_id', 'status'], 'employees_type_company_status_index');
            $table->index('name', 'employees_name_index');
        });
        Schema::table('payrolls', function (Blueprint $table) {
            $table->index(['employment_type', 'month', 'company_id', 'status'], 'payrolls_type_month_company_status_index');
            $table->index('employee_name', 'payrolls_employee_name_index');
        });
        Schema::table('activity_logs', function (Blueprint $table) {
            $table->index(['action', 'created_at'], 'activity_action_created_index');
        });
    }

    public function down()
    {
        Schema::table('employees', function (Blueprint $table) { $table->dropIndex('employees_type_company_status_index'); $table->dropIndex('employees_name_index'); });
        Schema::table('payrolls', function (Blueprint $table) { $table->dropIndex('payrolls_type_month_company_status_index'); $table->dropIndex('payrolls_employee_name_index'); });
        Schema::table('activity_logs', function (Blueprint $table) { $table->dropIndex('activity_action_created_index'); });
    }
}
