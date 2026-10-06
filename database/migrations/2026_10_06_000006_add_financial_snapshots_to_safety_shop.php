<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddFinancialSnapshotsToSafetyShop extends Migration
{
    public function up()
    {
        Schema::table('safety_shop_sale_lines', function (Blueprint $table) {
            $table->unsignedBigInteger('cost_cents')->default(0)->after('quantity');
        });
        Schema::table('safety_shop_returns', function (Blueprint $table) {
            $table->unsignedBigInteger('gross_refund_cents')->default(0)->after('refund_cents');
            $table->unsignedBigInteger('discount_refund_cents')->default(0)->after('gross_refund_cents');
            $table->unsignedBigInteger('tax_refund_cents')->default(0)->after('discount_refund_cents');
            $table->unsignedBigInteger('cost_reversal_cents')->default(0)->after('tax_refund_cents');
        });
        Schema::table('safety_shop_return_lines', function (Blueprint $table) {
            $table->unsignedBigInteger('cost_cents')->default(0)->after('quantity');
        });
        DB::table('safety_shop_sale_lines')->orderBy('id')->each(function ($line) {
            $cost=DB::table('safety_shop_products')->where('id',$line->product_id)->value('cost_cents')??0;
            DB::table('safety_shop_sale_lines')->where('id',$line->id)->update(['cost_cents'=>$cost]);
        });
    }

    public function down()
    {
        Schema::table('safety_shop_return_lines', fn (Blueprint $table) => $table->dropColumn('cost_cents'));
        Schema::table('safety_shop_returns', fn (Blueprint $table) => $table->dropColumn(['gross_refund_cents','discount_refund_cents','tax_refund_cents','cost_reversal_cents']));
        Schema::table('safety_shop_sale_lines', fn (Blueprint $table) => $table->dropColumn('cost_cents'));
    }
}
