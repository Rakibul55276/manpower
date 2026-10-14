<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void { Schema::table('safety_shop_sales',function(Blueprint $table){$table->foreignId('quotation_id')->nullable()->after('customer_id')->constrained('commercial_documents')->nullOnDelete();}); }
    public function down(): void { Schema::table('safety_shop_sales',function(Blueprint $table){$table->dropForeign(['quotation_id']);$table->dropColumn('quotation_id');}); }
};
