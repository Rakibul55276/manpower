<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('quotation_item_masters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->restrictOnDelete();
            $table->string('name');
            $table->string('sku', 50)->nullable();
            $table->string('unit', 20)->default('EA');
            $table->unsignedBigInteger('unit_price_cents')->default(0);
            $table->unsignedSmallInteger('tax_rate_units')->default(1500);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['company_id', 'is_active', 'name']);
        });
        Schema::table('commercial_document_lines', function (Blueprint $table) {
            $table->foreignId('quotation_item_master_id')->nullable()->after('product_id')->constrained('quotation_item_masters')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('commercial_document_lines', fn (Blueprint $table) => $table->dropConstrainedForeignId('quotation_item_master_id'));
        Schema::dropIfExists('quotation_item_masters');
    }
};
