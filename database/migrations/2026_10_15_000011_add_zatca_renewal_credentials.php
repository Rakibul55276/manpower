<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration{
    public function up(): void{Schema::table('zatca_devices',function(Blueprint $table){$table->longText('renewal_csr')->nullable()->after('csr');$table->text('renewal_private_key_encrypted')->nullable()->after('private_key_encrypted');});}
    public function down(): void{Schema::table('zatca_devices',fn(Blueprint $table)=>$table->dropColumn(['renewal_csr','renewal_private_key_encrypted']));}
};
