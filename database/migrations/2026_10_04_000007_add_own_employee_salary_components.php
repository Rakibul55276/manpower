<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddOwnEmployeeSalaryComponents extends Migration
{
    public function up()
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->string('directorate')->nullable()->after('company_id');
            $table->string('department')->nullable()->after('directorate');
            $table->unsignedBigInteger('meal_allowance_cents')->default(0)->after('monthly_salary_cents');
            $table->unsignedBigInteger('transportation_allowance_cents')->default(0)->after('meal_allowance_cents');
            $table->unsignedBigInteger('medical_allowance_cents')->default(0)->after('transportation_allowance_cents');
            $table->unsignedBigInteger('retirement_insurance_cents')->default(0)->after('medical_allowance_cents');
            $table->unsignedBigInteger('tax_cents')->default(0)->after('retirement_insurance_cents');
        });
        Schema::table('payrolls', function (Blueprint $table) {
            $table->string('directorate')->nullable()->after('designation_name');
            $table->string('department')->nullable()->after('directorate');
            $table->unsignedBigInteger('meal_allowance_cents')->default(0)->after('allowance_cents');
            $table->unsignedBigInteger('transportation_allowance_cents')->default(0)->after('meal_allowance_cents');
            $table->unsignedBigInteger('medical_allowance_cents')->default(0)->after('transportation_allowance_cents');
            $table->unsignedBigInteger('retirement_insurance_cents')->default(0)->after('deduction_cents');
            $table->unsignedBigInteger('tax_cents')->default(0)->after('retirement_insurance_cents');
        });
    }

    public function down()
    {
        Schema::table('payrolls', function (Blueprint $table) {
            $table->dropColumn(['directorate', 'department', 'meal_allowance_cents', 'transportation_allowance_cents', 'medical_allowance_cents', 'retirement_insurance_cents', 'tax_cents']);
        });
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn(['directorate', 'department', 'meal_allowance_cents', 'transportation_allowance_cents', 'medical_allowance_cents', 'retirement_insurance_cents', 'tax_cents']);
        });
    }
}
