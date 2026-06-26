<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('import_batches', function (Blueprint $table): void {
            $table->id();
            $table->date('week_ending');
            $table->string('status')->default('draft')->index();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('source_system')->default('Traverse Global');
            $table->string('currency', 3)->default('USD');
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('finalized_at')->nullable();
            $table->timestamps();

            $table->unique(['week_ending', 'source_system']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('import_batches');
    }
};
