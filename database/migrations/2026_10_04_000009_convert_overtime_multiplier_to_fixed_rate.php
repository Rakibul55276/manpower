<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class ConvertOvertimeMultiplierToFixedRate extends Migration
{
    public function up()
    {
        DB::table('employees')->where('overtime_rate_cents', 0)->update([
            'overtime_rate_cents' => DB::raw('ROUND(hourly_rate_cents * overtime_multiplier_units / 100)'),
            'overtime_multiplier_units' => 100,
        ]);

        DB::table('timesheets')->where('overtime_rate_cents', 0)->update([
            'overtime_rate_cents' => DB::raw('ROUND(hourly_rate_cents * overtime_multiplier_units / 100)'),
            'overtime_multiplier_units' => 100,
        ]);
    }

    public function down()
    {
        // Fixed overtime amounts cannot be reliably converted back to multipliers.
    }
}
