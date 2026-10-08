<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAttachmentToSafetyShopMovements extends Migration
{
    public function up()
    {
        Schema::table('safety_shop_movements', function (Blueprint $table) {
            $table->string('attachment_path', 500)->nullable()->after('notes');
            $table->string('attachment_name', 255)->nullable()->after('attachment_path');
            $table->string('attachment_mime', 100)->nullable()->after('attachment_name');
            $table->unsignedBigInteger('attachment_size')->nullable()->after('attachment_mime');
        });
    }

    public function down()
    {
        Schema::table('safety_shop_movements', function (Blueprint $table) {
            $table->dropColumn(['attachment_path', 'attachment_name', 'attachment_mime', 'attachment_size']);
        });
    }
}
