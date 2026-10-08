<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEmployeeAdvances extends Migration
{
    public function up()
    {
        Schema::create('employee_advances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->restrictOnDelete();
            $table->foreignId('company_id')->constrained()->restrictOnDelete();
            $table->foreignId('branch_id')->constrained()->restrictOnDelete();
            $table->date('advance_date');
            $table->unsignedBigInteger('amount_cents');
            $table->unsignedBigInteger('installment_cents');
            $table->string('reference', 100)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->index(['company_id', 'branch_id', 'advance_date']);
        });

        Schema::table('payrolls', function (Blueprint $table) {
            $table->unsignedBigInteger('advance_deduction_cents')->default(0)->after('deduction_cents');
        });

        Schema::create('employee_advance_repayments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_advance_id')->constrained()->restrictOnDelete();
            $table->foreignId('payroll_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('amount_cents');
            $table->timestamps();
            $table->unique(['employee_advance_id', 'payroll_id'], 'advance_payroll_unique');
        });
    }

    public function down()
    {
        Schema::dropIfExists('employee_advance_repayments');
        Schema::table('payrolls', function (Blueprint $table) { $table->dropColumn('advance_deduction_cents'); });
        Schema::dropIfExists('employee_advances');
    }
}
