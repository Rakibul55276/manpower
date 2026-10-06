<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddDiscountAndSplitPaymentsToSafetyShopSales extends Migration
{
    public function up()
    {
        Schema::table('safety_shop_sales', function (Blueprint $table) {
            $table->unsignedBigInteger('subtotal_cents')->default(0)->after('customer_phone');
            $table->unsignedBigInteger('discount_cents')->default(0)->after('subtotal_cents');
            $table->unsignedBigInteger('taxable_cents')->default(0)->after('discount_cents');
            $table->unsignedInteger('tax_rate_units')->default(0)->after('taxable_cents');
            $table->unsignedBigInteger('tax_cents')->default(0)->after('tax_rate_units');
            $table->unsignedBigInteger('cash_cents')->default(0)->after('paid_cents');
            $table->unsignedBigInteger('card_cents')->default(0)->after('cash_cents');
            $table->unsignedBigInteger('bank_cents')->default(0)->after('card_cents');
        });

        DB::table('safety_shop_sales')->orderBy('id')->each(function ($sale) {
            $payments = ['cash_cents' => 0, 'card_cents' => 0, 'bank_cents' => 0];
            if (array_key_exists($sale->payment_method.'_cents', $payments)) {
                $payments[$sale->payment_method.'_cents'] = $sale->paid_cents;
            }
            DB::table('safety_shop_sales')->where('id', $sale->id)->update(array_merge([
                'subtotal_cents' => $sale->total_cents,
                'taxable_cents' => $sale->total_cents,
            ], $payments));
        });
    }

    public function down()
    {
        Schema::table('safety_shop_sales', function (Blueprint $table) {
            $table->dropColumn(['subtotal_cents', 'discount_cents', 'taxable_cents', 'tax_rate_units', 'tax_cents', 'cash_cents', 'card_cents', 'bank_cents']);
        });
    }
}
