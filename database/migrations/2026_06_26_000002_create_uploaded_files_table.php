<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('uploaded_files', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('import_batch_id')->constrained()->cascadeOnDelete();
            $table->string('file_type')->index();
            $table->string('original_name');
            $table->string('storage_path');
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('size_bytes')->default(0);
            $table->string('sha256_checksum', 64)->index();
            $table->json('sheet_names')->nullable();
            $table->string('status')->default('uploaded')->index();
            $table->json('validation_errors')->nullable();
            $table->timestamps();

            $table->unique(['import_batch_id', 'file_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('uploaded_files');
    }
};
