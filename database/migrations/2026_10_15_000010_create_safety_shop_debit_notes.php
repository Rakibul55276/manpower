<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSafetyShopDebitNotes extends Migration
{
    public function up(): void
    {
        Schema::create('safety_shop_debit_notes', function (Blueprint $table) {
            $table->id();
            $table->uuid('request_key')->unique();
            $table->foreignId('sale_id')->constrained('safety_shop_sales')->restrictOnDelete();
            $table->foreignId('location_id')->constrained('safety_shop_masters')->restrictOnDelete();
            $table->date('issue_date');
            $table->string('reason', 500);
            $table->unsignedBigInteger('subtotal_cents');
            $table->unsignedInteger('tax_rate_units')->default(1500);
            $table->unsignedBigInteger('tax_cents');
            $table->unsignedBigInteger('total_cents');
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->index(['location_id', 'issue_date']);
        });
        Schema::create('safety_shop_debit_note_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('debit_note_id')->constrained('safety_shop_debit_notes')->cascadeOnDelete();
            $table->string('description', 255);
            $table->decimal('quantity', 12, 2);
            $table->string('unit', 20)->default('piece');
            $table->unsignedBigInteger('unit_price_cents');
            $table->unsignedBigInteger('subtotal_cents');
            $table->unsignedBigInteger('tax_cents');
            $table->unsignedBigInteger('total_cents');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('safety_shop_debit_note_lines');
        Schema::dropIfExists('safety_shop_debit_notes');
    }
}
