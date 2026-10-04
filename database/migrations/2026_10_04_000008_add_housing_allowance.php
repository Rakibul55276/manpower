<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddHousingAllowance extends Migration
{
    public function up()
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->unsignedBigInteger('housing_allowance_cents')->default(0)->after('transportation_allowance_cents');
        });
        Schema::table('payrolls', function (Blueprint $table) {
            $table->unsignedBigInteger('housing_allowance_cents')->default(0)->after('transportation_allowance_cents');
        });
    }

    public function down()
    {
        Schema::table('payrolls', function (Blueprint $table) { $table->dropColumn('housing_allowance_cents'); });
        Schema::table('employees', function (Blueprint $table) { $table->dropColumn('housing_allowance_cents'); });
    }
}
