<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
class AddDefaultShopLocationSetting extends Migration
{
    public function up(){Schema::table('safety_shop_receipt_settings',function(Blueprint $table){$table->foreignId('default_location_id')->nullable()->after('logo_path')->constrained('safety_shop_masters')->nullOnDelete();});$location=DB::table('safety_shop_masters')->where('type','location')->where('is_active',1)->orderBy('name')->value('id');if($location)DB::table('safety_shop_receipt_settings')->update(['default_location_id'=>$location]);}
    public function down(){Schema::table('safety_shop_receipt_settings',function(Blueprint $table){$table->dropForeign(['default_location_id']);$table->dropColumn('default_location_id');});}
}
