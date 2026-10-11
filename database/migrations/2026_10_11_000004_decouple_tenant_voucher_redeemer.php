<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DecoupleTenantVoucherRedeemer extends Migration
{
    public function up()
    {
        Schema::table('saas_voucher_redemptions', function (Blueprint $table) {
            $table->string('redeemed_by_name',150)->nullable()->after('redeemed_by');
            $table->string('redeemed_by_username',100)->nullable()->after('redeemed_by_name');
        });
        foreach (DB::table('saas_voucher_redemptions')->get() as $redemption) {
            $user=DB::table('users')->where('id',$redemption->redeemed_by)->first();
            DB::table('saas_voucher_redemptions')->where('id',$redemption->id)->update([
                'redeemed_by_name'=>$user->name ?? 'Company administrator',
                'redeemed_by_username'=>$user->username ?? null,
            ]);
        }
        if (DB::connection()->getDriverName() !== 'sqlite') {
            Schema::table('saas_voucher_redemptions', function (Blueprint $table) {
                $table->dropForeign(['redeemed_by']);
                $table->dropColumn('redeemed_by');
            });
        }
    }
    public function down()
    {
        Schema::table('saas_voucher_redemptions', function (Blueprint $table) {
            if (DB::connection()->getDriverName() !== 'sqlite') $table->foreignId('redeemed_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->dropColumn(['redeemed_by_name','redeemed_by_username']);
        });
    }
}
