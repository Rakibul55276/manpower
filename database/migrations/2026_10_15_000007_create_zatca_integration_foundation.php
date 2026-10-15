<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('zatca_devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->unique()->constrained()->restrictOnDelete();
            $table->string('environment', 20)->default('disabled');
            $table->string('status', 30)->default('not_configured');
            $table->string('solution_unit_name')->default('Safety Shop EGS');
            $table->string('egs_serial_number')->nullable();
            $table->unsignedBigInteger('last_icv')->default(0);
            $table->string('previous_invoice_hash', 255)->nullable();
            $table->longText('csr')->nullable();
            $table->text('private_key_encrypted')->nullable();
            $table->longText('compliance_certificate')->nullable();
            $table->text('compliance_secret_encrypted')->nullable();
            $table->string('compliance_request_id')->nullable();
            $table->longText('production_certificate')->nullable();
            $table->text('production_secret_encrypted')->nullable();
            $table->string('production_request_id')->nullable();
            $table->timestamp('certificate_expires_at')->nullable();
            $table->timestamp('last_connected_at')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamps();
        });

        Schema::create('zatca_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->restrictOnDelete();
            $table->foreignId('device_id')->nullable()->constrained('zatca_devices')->restrictOnDelete();
            $table->string('source_type', 40);
            $table->unsignedBigInteger('source_id');
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('icv');
            $table->string('document_kind', 30);
            $table->string('invoice_type', 20);
            $table->string('previous_invoice_hash', 255)->nullable();
            $table->string('invoice_hash', 64);
            $table->string('status', 30)->default('prepared');
            $table->string('submission_mode', 20);
            $table->longText('unsigned_xml');
            $table->longText('signed_xml')->nullable();
            $table->longText('cleared_xml')->nullable();
            $table->json('validation_results')->nullable();
            $table->json('request_payload')->nullable();
            $table->json('response_payload')->nullable();
            $table->unsignedInteger('attempt_count')->default(0);
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('next_retry_at')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamps();
            $table->unique(['source_type', 'source_id']);
            $table->unique(['company_id', 'icv']);
            $table->index(['company_id', 'status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('zatca_documents');
        Schema::dropIfExists('zatca_devices');
    }
};
