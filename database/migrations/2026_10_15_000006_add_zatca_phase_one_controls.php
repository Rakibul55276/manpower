<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('safety_shop_invoice_sequences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->unique()->constrained()->restrictOnDelete();
            $table->unsignedBigInteger('last_number')->default(0);
            $table->timestamps();
        });

        Schema::table('safety_shop_sales', function (Blueprint $table) {
            $table->string('invoice_type', 20)->default('simplified')->after('invoice_number')->index();
            $table->unsignedBigInteger('invoice_sequence_number')->nullable()->after('invoice_type')->index();
        });

        DB::table('safety_shop_sales')->where('customer_type', 'company')->update(['invoice_type' => 'standard']);
    }

    public function down(): void
    {
        Schema::table('safety_shop_sales', function (Blueprint $table) {
            $table->dropIndex(['invoice_type']);
            $table->dropIndex(['invoice_sequence_number']);
            $table->dropColumn(['invoice_type', 'invoice_sequence_number']);
        });
        Schema::dropIfExists('safety_shop_invoice_sequences');
    }
};
