<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void { Schema::table('commercial_documents',function(Blueprint $table){
        $table->foreignId('revision_of_id')->nullable()->after('quotation_id')->constrained('commercial_documents')->nullOnDelete();
        $table->unsignedSmallInteger('revision_number')->default(0)->after('revision_of_id');
        $table->foreignId('superseded_by_id')->nullable()->after('revision_number')->constrained('commercial_documents')->nullOnDelete();
        $table->foreignId('cancelled_by')->nullable()->after('approved_at')->constrained('users')->nullOnDelete();
        $table->timestamp('cancelled_at')->nullable()->after('cancelled_by');
        $table->text('cancellation_reason')->nullable()->after('cancelled_at');
    }); }
    public function down(): void { Schema::table('commercial_documents',function(Blueprint $table){
        $table->dropForeign(['revision_of_id']);$table->dropForeign(['superseded_by_id']);$table->dropForeign(['cancelled_by']);
        $table->dropColumn(['revision_of_id','revision_number','superseded_by_id','cancelled_by','cancelled_at','cancellation_reason']);
    }); }
};
