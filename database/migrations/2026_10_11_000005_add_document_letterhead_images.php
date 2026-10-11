<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDocumentLetterheadImages extends Migration
{
    public function up()
    {
        Schema::table('safety_shop_receipt_settings', function (Blueprint $table) {
            $table->string('header_image_path')->nullable()->after('logo_path');
            $table->string('footer_image_path')->nullable()->after('header_image_path');
        });
    }

    public function down()
    {
        Schema::table('safety_shop_receipt_settings', function (Blueprint $table) {
            $table->dropColumn(['header_image_path', 'footer_image_path']);
        });
    }
}
