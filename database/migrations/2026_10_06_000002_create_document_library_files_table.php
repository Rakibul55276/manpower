<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDocumentLibraryFilesTable extends Migration
{
    public function up()
    {
        Schema::create('document_library_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('uploaded_by')->constrained('users')->restrictOnDelete();
            $table->string('title', 180);
            $table->string('category', 100)->nullable();
            $table->string('document_number', 100)->nullable();
            $table->date('issued_on')->nullable();
            $table->date('expires_on')->nullable();
            $table->string('original_name', 255);
            $table->string('storage_path', 500)->unique();
            $table->string('mime_type', 100);
            $table->unsignedBigInteger('size_bytes');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['owner_id', 'category']);
            $table->index('expires_on');
        });
    }

    public function down()
    {
        Schema::dropIfExists('document_library_files');
    }
}
