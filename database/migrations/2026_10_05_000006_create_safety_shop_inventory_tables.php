<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
class CreateSafetyShopInventoryTables extends Migration
{
    public function up()
    {
        Schema::create('safety_shop_masters', function (Blueprint $t) {
            $t->id(); $t->string('type', 20); $t->string('name', 150);
            $t->string('phone', 50)->nullable(); $t->string('email', 150)->nullable();
            $t->string('address', 500)->nullable(); $t->boolean('is_active')->default(true);
            $t->timestamps(); $t->unique(['type', 'name']);
        });
        Schema::create('safety_shop_products', function (Blueprint $t) {
            $t->id(); $t->string('barcode', 100)->nullable()->unique(); $t->string('sku', 50)->unique(); $t->string('name', 150);
            $t->foreignId('category_id')->nullable()->constrained('safety_shop_masters')->restrictOnDelete();
            $t->string('brand', 100)->nullable(); $t->string('size', 50)->nullable();
            $t->string('unit', 30)->default('piece'); $t->string('safety_standard', 150)->nullable();
            $t->unsignedInteger('reorder_level')->default(0);
            $t->unsignedBigInteger('cost_cents')->default(0); $t->unsignedBigInteger('price_cents')->default(0);
            $t->boolean('is_active')->default(true); $t->text('notes')->nullable(); $t->timestamps();
        });
        Schema::create('safety_shop_stocks', function (Blueprint $t) {
            $t->id(); $t->foreignId('product_id')->constrained('safety_shop_products')->restrictOnDelete();
            $t->foreignId('location_id')->constrained('safety_shop_masters')->restrictOnDelete();
            $t->unsignedInteger('quantity')->default(0); $t->timestamps();
            $t->unique(['product_id', 'location_id']);
        });
        Schema::create('safety_shop_sales', function (Blueprint $t) {
            $t->id(); $t->uuid('request_key')->unique();
            $t->foreignId('location_id')->constrained('safety_shop_masters')->restrictOnDelete();
            $t->string('customer',150); $t->unsignedBigInteger('total_cents');
            $t->unsignedBigInteger('paid_cents'); $t->string('payment_method',20);
            $t->foreignId('created_by')->constrained('users')->restrictOnDelete(); $t->timestamps();
        });
        Schema::create('safety_shop_sale_lines', function (Blueprint $t) {
            $t->id(); $t->foreignId('sale_id')->constrained('safety_shop_sales')->restrictOnDelete();
            $t->foreignId('product_id')->constrained('safety_shop_products')->restrictOnDelete();
            $t->string('sku',50); $t->string('barcode',100)->nullable(); $t->string('name',150); $t->string('unit',30);
            $t->unsignedInteger('quantity'); $t->unsignedBigInteger('price_cents'); $t->unsignedBigInteger('total_cents');
            $t->timestamps();
        });
        Schema::create('safety_shop_movements', function (Blueprint $t) {
            $t->id(); $t->uuid('request_key')->unique(); $t->string('type', 20);
            $t->foreignId('product_id')->constrained('safety_shop_products')->restrictOnDelete();
            $t->foreignId('location_id')->constrained('safety_shop_masters')->restrictOnDelete();
            $t->foreignId('destination_id')->nullable()->constrained('safety_shop_masters')->restrictOnDelete();
            $t->foreignId('supplier_id')->nullable()->constrained('safety_shop_masters')->restrictOnDelete();
            $t->integer('quantity'); $t->unsignedInteger('balance_after');
            $t->unsignedInteger('destination_balance_after')->nullable();
            $t->date('movement_date'); $t->string('reference', 100)->nullable();
            $t->string('recipient', 150)->nullable(); $t->text('notes');
            $t->foreignId('created_by')->constrained('users')->restrictOnDelete(); $t->timestamps();
            $t->index(['movement_date', 'type']); $t->index(['product_id', 'location_id']);
        });
    }
    public function down()
    {
        Schema::dropIfExists('safety_shop_sale_lines'); Schema::dropIfExists('safety_shop_sales');
        Schema::dropIfExists('safety_shop_movements'); Schema::dropIfExists('safety_shop_stocks');
        Schema::dropIfExists('safety_shop_products'); Schema::dropIfExists('safety_shop_masters');
    }
}
