<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('safety_shop_customers', function (Blueprint $table) {
            $table->string('bank_account_name',150)->nullable();
            $table->string('bank_name',150)->nullable();
            $table->string('bank_account_number',50)->nullable();
            $table->string('bank_iban',34)->nullable();
            $table->string('bank_branch',100)->nullable();
        });
    }
    public function down(): void {
        Schema::table('safety_shop_customers', function (Blueprint $table) {
            $table->dropColumn(['bank_account_name','bank_name','bank_account_number','bank_iban','bank_branch']);
        });
    }
};
