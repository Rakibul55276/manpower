<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCustomerApprovalStatus extends Migration
{
    public function up(){Schema::table('safety_shop_customers',function(Blueprint $table){$table->string('approval_status',20)->default('approved')->after('is_active')->index();$table->foreignId('approved_by')->nullable()->after('approval_status')->constrained('users')->nullOnDelete();$table->timestamp('approved_at')->nullable()->after('approved_by');});}
    public function down(){Schema::table('safety_shop_customers',function(Blueprint $table){$table->dropForeign(['approved_by']);$table->dropColumn(['approval_status','approved_by','approved_at']);});}
}
