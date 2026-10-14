<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('user_module_access', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('company_id')->constrained()->restrictOnDelete();
            $table->string('module', 30);
            $table->foreignId('branch_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('role', 30);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['user_id', 'module']);
            $table->index(['company_id', 'module', 'branch_id']);
        });

        $now = now();
        DB::table('users')->whereIn('role', ['manager', 'issuer'])->whereNotNull('company_id')->orderBy('id')->each(function ($user) use ($now) {
            foreach (['manpower', 'safety-shop'] as $module) {
                DB::table('user_module_access')->insert([
                    'user_id' => $user->id, 'company_id' => $user->company_id, 'module' => $module,
                    'branch_id' => $user->branch_id, 'role' => $user->role, 'is_active' => $user->is_active,
                    'created_at' => $now, 'updated_at' => $now,
                ]);
            }
        });

        Schema::table('document_library_files', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->after('id')->constrained()->restrictOnDelete();
            $table->string('module', 30)->default('manpower')->after('company_id');
            $table->foreignId('branch_id')->nullable()->after('module')->constrained()->restrictOnDelete();
            $table->index(['company_id', 'module', 'branch_id'], 'documents_module_scope_index');
        });
        DB::table('document_library_files')->orderBy('id')->each(function ($document) {
            $owner = DB::table('users')->where('id', $document->owner_id)->first(['company_id', 'branch_id']);
            if ($owner) DB::table('document_library_files')->where('id', $document->id)->update([
                'company_id' => $owner->company_id, 'branch_id' => $owner->branch_id, 'module' => 'manpower',
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('document_library_files', function (Blueprint $table) {
            $table->dropIndex('documents_module_scope_index');
            $table->dropConstrainedForeignId('branch_id');
            $table->dropColumn('module');
            $table->dropConstrainedForeignId('company_id');
        });
        Schema::dropIfExists('user_module_access');
    }
};
