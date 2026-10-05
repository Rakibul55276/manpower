<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class SeparateZatcaFromInvoicePlatform extends Migration
{
    public function up()
    {
        DB::table('invoice_settings')->where('invoice_prefix', 'DEMO')->update([
            'invoice_prefix' => 'INV',
            'demo_mode' => false,
            'zatca_environment' => 'disabled',
            'updated_at' => now(),
        ]);
        DB::table('invoices')->whereIn('zatca_status', ['not_submitted', 'mock_validated'])->update([
            'zatca_status' => 'not_connected',
            'zatca_message' => null,
            'updated_at' => now(),
        ]);
    }

    public function down()
    {
        DB::table('invoice_settings')->where('invoice_prefix', 'INV')->update([
            'invoice_prefix' => 'DEMO',
            'demo_mode' => true,
            'updated_at' => now(),
        ]);
    }
}
