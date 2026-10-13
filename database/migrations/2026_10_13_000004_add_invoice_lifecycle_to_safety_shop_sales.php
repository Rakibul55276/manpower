<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
class AddInvoiceLifecycleToSafetyShopSales extends Migration
{
    public function up(): void
    {
        Schema::table('safety_shop_sales',function(Blueprint $table){
            $table->string('status',20)->default('finalized')->after('payment_received')->index();
            $table->foreignId('replaces_sale_id')->nullable()->after('status')->constrained('safety_shop_sales')->restrictOnDelete();
            $table->text('cancel_reason')->nullable()->after('replaces_sale_id');
            $table->foreignId('cancelled_by')->nullable()->after('cancel_reason')->constrained('users')->restrictOnDelete();
            $table->timestamp('cancelled_at')->nullable()->after('cancelled_by');
            $table->unique('replaces_sale_id');
        });
    }
    public function down(): void
    {
        Schema::table('safety_shop_sales',function(Blueprint $table){$table->dropUnique(['replaces_sale_id']);$table->dropForeign(['replaces_sale_id']);$table->dropForeign(['cancelled_by']);$table->dropColumn(['status','replaces_sale_id','cancel_reason','cancelled_by','cancelled_at']);});
    }
}
