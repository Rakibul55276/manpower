<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class BackfillSafetyShopReturnFinancials extends Migration
{
    public function up()
    {
        DB::table('safety_shop_returns')->orderBy('id')->each(function ($return) {
            $sale=DB::table('safety_shop_sales')->where('id',$return->sale_id)->first();
            if (!$sale) return;
            $gross=0; $discount=0; $tax=0; $cost=0;
            $lines=DB::table('safety_shop_return_lines')->where('return_id',$return->id)->get();
            foreach ($lines as $returnLine) {
                $saleLine=DB::table('safety_shop_sale_lines')->where('id',$returnLine->sale_line_id)->first();
                if (!$saleLine) continue;
                $lineGross=$returnLine->quantity*$saleLine->price_cents;
                $lineDiscount=$sale->subtotal_cents?(int)round($lineGross*$sale->discount_cents/$sale->subtotal_cents):0;
                $lineTax=(int)round(($lineGross-$lineDiscount)*$sale->tax_rate_units/10000);
                $lineCost=$returnLine->quantity*$saleLine->cost_cents;
                $gross+=$lineGross; $discount+=$lineDiscount; $tax+=$lineTax; $cost+=$lineCost;
                DB::table('safety_shop_return_lines')->where('id',$returnLine->id)->update(['cost_cents'=>$lineCost]);
            }
            if (($gross-$discount+$tax) !== (int)$return->refund_cents && $gross > 0) {
                $tax=max(0,(int)$return->refund_cents-($gross-$discount));
            }
            DB::table('safety_shop_returns')->where('id',$return->id)->update(['gross_refund_cents'=>$gross,'discount_refund_cents'=>$discount,'tax_refund_cents'=>$tax,'cost_reversal_cents'=>$cost]);
        });
    }

    public function down() {}
}
