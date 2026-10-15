<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('commercial_documents', function (Blueprint $table) {
            $table->string('discount_type', 10)->default('fixed')->after('notes');
            $table->decimal('discount_value', 12, 2)->default(0)->after('discount_type');
            $table->unsignedBigInteger('discount_cents')->default(0)->after('subtotal_cents');
        });
    }

    public function down(): void
    {
        Schema::table('commercial_documents', function (Blueprint $table) {
            $table->dropColumn(['discount_type', 'discount_value', 'discount_cents']);
        });
    }
};
