<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('generated_reports', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('import_batch_id')->constrained()->cascadeOnDelete();
            $table->string('report_type')->index();
            $table->string('status')->default('queued')->index();
            $table->string('file_name')->nullable();
            $table->string('storage_path')->nullable();
            $table->string('sha256_checksum', 64)->nullable()->index();
            $table->json('summary')->nullable();
            $table->json('errors')->nullable();
            $table->foreignId('generated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('generated_at')->nullable();
            $table->timestamps();

            $table->unique(['import_batch_id', 'report_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('generated_reports');
    }
};
