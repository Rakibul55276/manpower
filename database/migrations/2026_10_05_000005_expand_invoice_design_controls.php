<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ExpandInvoiceDesignControls extends Migration
{
    public function up(){Schema::table('invoice_settings',function(Blueprint $table){$table->string('design_header_layout',20)->default('split');$table->string('design_title_alignment',10)->default('center');$table->unsignedSmallInteger('design_logo_width')->default(58);$table->decimal('design_font_size',4,1)->default(7.3);$table->string('design_border_color',7)->default('#86999d');$table->boolean('show_company_cr')->default(true);$table->boolean('show_seller_details')->default(true);$table->boolean('show_customer_details')->default(true);$table->boolean('show_references')->default(true);$table->boolean('show_amount_words')->default(true);$table->boolean('show_notes')->default(true);$table->boolean('show_footer_uuid')->default(true);});}
    public function down(){Schema::table('invoice_settings',function(Blueprint $table){$table->dropColumn(['design_header_layout','design_title_alignment','design_logo_width','design_font_size','design_border_color','show_company_cr','show_seller_details','show_customer_details','show_references','show_amount_words','show_notes','show_footer_uuid']);});}
}
