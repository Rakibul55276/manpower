<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('safety_shop_sale_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')->constrained('safety_shop_sales')->restrictOnDelete();
            $table->date('payment_date');
            $table->string('method', 20);
            $table->unsignedBigInteger('amount_cents');
            $table->string('reference', 100)->nullable();
            $table->string('notes', 500)->nullable();
            $table->string('source', 20)->default('recorded');
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('reversed_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamp('reversed_at')->nullable();
            $table->string('reversal_reason', 500)->nullable();
            $table->timestamps();
            $table->index(['sale_id', 'reversed_at']);
        });

        DB::table('safety_shop_sales')->where('status', '<>', 'draft')->orderBy('id')->each(function ($sale) {
            foreach (['cash', 'card', 'bank'] as $method) {
                $amount=(int)($sale->{$method.'_cents'} ?? 0);
                if ($amount <= 0) continue;
                DB::table('safety_shop_sale_payments')->insert([
                    'sale_id'=>$sale->id,
                    'payment_date'=>substr((string)$sale->created_at,0,10),
                    'method'=>$method,
                    'amount_cents'=>$amount,
                    'reference'=>'Opening invoice payment',
                    'source'=>'initial',
                    'created_by'=>$sale->created_by,
                    'created_at'=>$sale->created_at,
                    'updated_at'=>$sale->updated_at,
                ]);
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('safety_shop_sale_payments');
    }
};
