<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSafetyShopReturnsTables extends Migration
{
    public function up()
    {
        Schema::create('safety_shop_returns', function (Blueprint $table) {
            $table->id();
            $table->uuid('request_key')->unique();
            $table->foreignId('sale_id')->constrained('safety_shop_sales')->restrictOnDelete();
            $table->foreignId('location_id')->constrained('safety_shop_masters')->restrictOnDelete();
            $table->string('refund_method', 20);
            $table->unsignedBigInteger('refund_cents');
            $table->string('reason', 500);
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->index(['sale_id', 'created_at']);
        });

        Schema::create('safety_shop_return_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('return_id')->constrained('safety_shop_returns')->restrictOnDelete();
            $table->foreignId('sale_line_id')->constrained('safety_shop_sale_lines')->restrictOnDelete();
            $table->foreignId('product_id')->constrained('safety_shop_products')->restrictOnDelete();
            $table->unsignedInteger('quantity');
            $table->unsignedBigInteger('refund_cents');
            $table->timestamps();
            $table->unique(['return_id', 'sale_line_id']);
            $table->index('sale_line_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('safety_shop_return_lines');
        Schema::dropIfExists('safety_shop_returns');
    }
}
