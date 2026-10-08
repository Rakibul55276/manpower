<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class EnableSaasVouchers extends Migration
{
    public function up()
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->string('company_code', 30)->nullable()->unique()->after('name');
            $table->string('subscription_status', 20)->default('inactive')->after('is_active');
            $table->date('subscription_started_at')->nullable()->after('subscription_status');
            $table->date('subscription_expires_at')->nullable()->after('subscription_started_at');
            $table->date('subscription_grace_until')->nullable()->after('subscription_expires_at');
            $table->index(['subscription_status', 'subscription_expires_at']);
        });

        Schema::create('saas_vouchers', function (Blueprint $table) {
            $table->id();
            $table->char('code_hash', 64)->unique();
            $table->string('code_prefix', 12);
            $table->unsignedInteger('duration_days');
            $table->string('status', 20)->default('unused');
            $table->foreignId('assigned_company_id')->nullable()->constrained('companies')->restrictOnDelete();
            $table->foreignId('redeemed_company_id')->nullable()->constrained('companies')->restrictOnDelete();
            $table->date('valid_until')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('redeemed_at')->nullable();
            $table->timestamps();
            $table->index(['status', 'valid_until']);
        });

        Schema::create('saas_voucher_redemptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('voucher_id')->unique()->constrained('saas_vouchers')->restrictOnDelete();
            $table->foreignId('company_id')->constrained('companies')->restrictOnDelete();
            $table->foreignId('redeemed_by')->constrained('users')->restrictOnDelete();
            $table->date('previous_expiry')->nullable();
            $table->date('new_expiry');
            $table->timestamp('redeemed_at');
            $table->index(['company_id', 'redeemed_at']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('saas_voucher_redemptions');
        Schema::dropIfExists('saas_vouchers');
        Schema::table('companies', function (Blueprint $table) {
            $table->dropIndex(['subscription_status', 'subscription_expires_at']);
            $table->dropUnique(['company_code']);
            $table->dropColumn(['company_code', 'subscription_status', 'subscription_started_at', 'subscription_expires_at', 'subscription_grace_until']);
        });
    }
}
