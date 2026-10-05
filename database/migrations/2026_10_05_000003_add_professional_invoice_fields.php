<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddProfessionalInvoiceFields extends Migration
{
    public function up()
    {
        Schema::table('invoice_settings', function (Blueprint $table) {
            $table->string('bank_name')->nullable()->after('country_code');
            $table->string('bank_account_name')->nullable()->after('bank_name');
            $table->string('bank_account_number', 40)->nullable()->after('bank_account_name');
            $table->string('iban', 34)->nullable()->after('bank_account_number');
            $table->string('bank_branch')->nullable()->after('iban');
        });
        Schema::table('invoices', function (Blueprint $table) {
            $table->date('supply_date')->nullable()->after('issue_date');
            $table->date('due_date')->nullable()->after('supply_date');
            $table->string('contract_po')->nullable()->after('due_date');
            $table->string('delivery_note')->nullable()->after('contract_po');
            $table->string('invoice_period')->nullable()->after('delivery_note');
            $table->string('project_reference')->nullable()->after('invoice_period');
        });
        Schema::table('invoice_lines', function (Blueprint $table) {$table->string('description_ar')->nullable()->after('description');});
    }

    public function down()
    {
        Schema::table('invoice_lines', function (Blueprint $table) {$table->dropColumn('description_ar');});
        Schema::table('invoices', function (Blueprint $table) {$table->dropColumn(['supply_date','due_date','contract_po','delivery_note','invoice_period','project_reference']);});
        Schema::table('invoice_settings', function (Blueprint $table) {$table->dropColumn(['bank_name','bank_account_name','bank_account_number','iban','bank_branch']);});
    }
}
