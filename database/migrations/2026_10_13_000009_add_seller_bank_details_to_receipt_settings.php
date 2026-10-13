<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::table('safety_shop_receipt_settings', function (Blueprint $table) {
            foreach (['bank_account_name','bank_name','bank_account_number','bank_iban','bank_branch'] as $field) $table->string($field,150)->nullable();
        });
    }
    public function down(): void {
        Schema::table('safety_shop_receipt_settings', function (Blueprint $table) {
            $table->dropColumn(['bank_account_name','bank_name','bank_account_number','bank_iban','bank_branch']);
        });
    }
};
