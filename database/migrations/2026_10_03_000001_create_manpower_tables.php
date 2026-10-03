<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateManpowerTables extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('manager');
            $table->boolean('is_active')->default(true);
        });
        foreach (['companies', 'designations'] as $name) {
            Schema::create($name, function (Blueprint $table) {
                $table->id();
                $table->string('name')->unique();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }
        Schema::create('company_user', function (Blueprint $table) {
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->primary(['company_id', 'user_id']);
        });
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('photo_path');
            $table->string('document_path')->nullable();
            $table->string('iqama_number', 30)->unique();
            $table->string('passport_number', 30)->unique();
            $table->string('phone', 30);
            $table->foreignId('designation_id')->constrained()->restrictOnDelete();
            $table->foreignId('company_id')->constrained()->restrictOnDelete();
            $table->json('previous_experience')->nullable();
            $table->string('blood_group', 3);
            $table->unsignedInteger('hourly_rate_cents');
            $table->string('salary_type')->default('hourly');
            $table->string('employment_type')->default('rental')->index();
            $table->unsignedInteger('monthly_salary_cents')->default(0);
            $table->unsignedInteger('overtime_multiplier_units')->default(150);
            $table->date('joined_on');
            $table->string('status')->default('active');
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->index(['company_id', 'status']);
        });
        Schema::create('payrolls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->restrictOnDelete();
            $table->string('month', 7);
            $table->foreignId('company_id')->constrained()->restrictOnDelete();
            $table->string('salary_type');
            $table->string('employment_type');
            $table->string('employee_name');
            $table->string('company_name');
            $table->string('designation_name');
            $table->string('iqama_number');
            $table->unsignedInteger('regular_units');
            $table->unsignedInteger('overtime_units');
            $table->unsignedBigInteger('regular_pay_cents');
            $table->unsignedBigInteger('overtime_pay_cents');
            $table->unsignedBigInteger('allowance_cents')->default(0);
            $table->unsignedBigInteger('deduction_cents')->default(0);
            $table->unsignedBigInteger('net_pay_cents');
            $table->string('status')->default('draft');
            $table->text('notes')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('paid_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->unique(['employee_id', 'month']);
        });
        Schema::create('timesheets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->restrictOnDelete();
            $table->date('work_date');
            $table->unsignedInteger('regular_units');
            $table->unsignedInteger('overtime_units')->default(0);
            $table->unsignedInteger('hourly_rate_cents');
            $table->unsignedInteger('overtime_multiplier_units');
            $table->string('status')->default('pending');
            $table->text('notes')->nullable();
            $table->text('review_note')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('payroll_id')->nullable()->constrained()->restrictOnDelete();
            $table->timestamps();
            $table->unique(['employee_id', 'work_date']);
            $table->index(['work_date', 'status']);
        });
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action');
            $table->string('subject');
            $table->timestamps();
        });
    }

    public function down()
    {
        foreach (['activity_logs', 'timesheets', 'payrolls', 'employees', 'company_user', 'designations', 'companies'] as $name) {
            Schema::dropIfExists($name);
        }
        Schema::table('users', function (Blueprint $table) { $table->dropColumn(['role', 'is_active']); });
    }
}
