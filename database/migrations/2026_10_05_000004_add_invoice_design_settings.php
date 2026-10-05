<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddInvoiceDesignSettings extends Migration
{
    public function up(){Schema::table('invoice_settings',function(Blueprint $table){$table->string('logo_path')->nullable();$table->string('design_primary_color',7)->default('#167896');$table->string('design_text_color',7)->default('#17343a');$table->string('design_header_bg',7)->default('#e5f4f8');$table->string('invoice_title')->default('TAX INVOICE');$table->string('invoice_title_ar')->default('فاتورة ضريبية');$table->string('design_density',10)->default('compact');$table->text('invoice_footer')->nullable();$table->boolean('show_bank_details')->default(true);$table->boolean('show_signatures')->default(true);$table->boolean('show_qr')->default(true);});}
    public function down(){Schema::table('invoice_settings',function(Blueprint $table){$table->dropColumn(['logo_path','design_primary_color','design_text_color','design_header_bg','invoice_title','invoice_title_ar','design_density','invoice_footer','show_bank_details','show_signatures','show_qr']);});}
}
