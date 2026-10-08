<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddSkuPrefixToSafetyShopCategories extends Migration
{
    public function up()
    {
        Schema::table('safety_shop_masters', function (Blueprint $table) {
            $table->string('sku_prefix', 10)->nullable()->after('name');
        });

        $used = [];
        foreach (DB::table('safety_shop_masters')->where('type', 'category')->orderBy('id')->get() as $category) {
            $words = preg_split('/[^A-Za-z0-9]+/', trim($category->name), -1, PREG_SPLIT_NO_EMPTY);
            $base = count($words) > 1
                ? implode('', array_map(fn ($word) => strtoupper(substr($word, 0, 1)), array_slice($words, 0, 6)))
                : strtoupper(substr($words[0] ?? 'CAT', 0, 3));
            $base = substr($base ?: 'CAT', 0, 10);
            $key = ($category->company_id ?? 'none').':'.$base;
            $number = ($used[$key] ?? 0) + 1;
            $used[$key] = $number;
            $prefix = $number === 1 ? $base : substr($base, 0, 10 - strlen((string) $number)).$number;
            DB::table('safety_shop_masters')->where('id', $category->id)->update(['sku_prefix' => $prefix]);
        }

        Schema::table('safety_shop_masters', function (Blueprint $table) {
            $table->unique(['company_id', 'type', 'sku_prefix'], 'ss_master_company_type_prefix_unique');
        });
    }

    public function down()
    {
        Schema::table('safety_shop_masters', function (Blueprint $table) {
            $table->dropUnique('ss_master_company_type_prefix_unique');
            $table->dropColumn('sku_prefix');
        });
    }
}
