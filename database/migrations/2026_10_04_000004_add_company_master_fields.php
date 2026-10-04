<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCompanyMasterFields extends Migration
{
    public function up()
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->string('location', 150)->default('Not specified')->after('name');
            $table->string('registration_number', 100)->nullable()->after('location');
            $table->string('contact_person', 150)->nullable()->after('registration_number');
            $table->string('phone', 50)->nullable()->after('contact_person');
            $table->string('email', 150)->nullable()->after('phone');
            $table->text('address')->nullable()->after('email');
            $table->text('notes')->nullable()->after('address');
        });
    }

    public function down()
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn(['location', 'registration_number', 'contact_person', 'phone', 'email', 'address', 'notes']);
        });
    }
}
