<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTenantDatabaseToCompanies extends Migration
{
    public function up()
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->string('db_host', 190)->nullable()->after('subscription_grace_until');
            $table->unsignedSmallInteger('db_port')->default(3306)->after('db_host');
            $table->string('db_name', 190)->nullable()->after('db_port');
            $table->string('db_username', 190)->nullable()->after('db_name');
            $table->text('db_password_encrypted')->nullable()->after('db_username');
            $table->timestamp('db_verified_at')->nullable()->after('db_password_encrypted');
            $table->unique(['db_host', 'db_port', 'db_name'], 'companies_tenant_database_unique');
        });
    }

    public function down()
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropUnique('companies_tenant_database_unique');
            $table->dropColumn(['db_host', 'db_port', 'db_name', 'db_username', 'db_password_encrypted', 'db_verified_at']);
        });
    }
}
