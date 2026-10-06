<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateSafetyShopCustomers extends Migration
{
    public function up()
    {
        Schema::create('safety_shop_customers', function (Blueprint $table) {
            $table->id(); $table->string('name',150); $table->string('phone',30)->nullable()->unique();
            $table->string('email',150)->nullable(); $table->string('address',500)->nullable();
            $table->unsignedInteger('purchase_count')->default(0); $table->unsignedBigInteger('lifetime_value_cents')->default(0);
            $table->timestamp('last_purchase_at')->nullable(); $table->boolean('is_active')->default(true); $table->timestamps();
            $table->index('name');
        });
        Schema::table('safety_shop_sales', function (Blueprint $table) {
            $table->foreignId('customer_id')->nullable()->after('location_id')->constrained('safety_shop_customers')->nullOnDelete();
            $table->string('customer_email',150)->nullable()->after('customer_phone');
            $table->string('customer_address',500)->nullable()->after('customer_email');
        });
        DB::table('safety_shop_sales')->where('customer','<>','Walk-in customer')->orderBy('id')->each(function($sale){
            $phone=$sale->customer_phone?preg_replace('/[^0-9+]/','',$sale->customer_phone):null;
            $customer=$phone?DB::table('safety_shop_customers')->where('phone',$phone)->first():DB::table('safety_shop_customers')->where('name',$sale->customer)->whereNull('phone')->first();
            if(!$customer){$id=DB::table('safety_shop_customers')->insertGetId(['name'=>$sale->customer,'phone'=>$phone,'purchase_count'=>0,'lifetime_value_cents'=>0,'is_active'=>1,'created_at'=>$sale->created_at,'updated_at'=>$sale->updated_at]);$customer=DB::table('safety_shop_customers')->where('id',$id)->first();}
            DB::table('safety_shop_sales')->where('id',$sale->id)->update(['customer_id'=>$customer->id]);
        });
        DB::table('safety_shop_customers')->orderBy('id')->each(function($customer){$stats=DB::table('safety_shop_sales')->where('customer_id',$customer->id)->selectRaw('COUNT(*) purchases, COALESCE(SUM(total_cents),0) value, MAX(created_at) last_purchase')->first();DB::table('safety_shop_customers')->where('id',$customer->id)->update(['purchase_count'=>$stats->purchases,'lifetime_value_cents'=>$stats->value,'last_purchase_at'=>$stats->last_purchase]);});
    }
    public function down()
    {
        Schema::table('safety_shop_sales', function(Blueprint $table){$table->dropForeign(['customer_id']);$table->dropColumn(['customer_id','customer_email','customer_address']);});
        Schema::dropIfExists('safety_shop_customers');
    }
}
