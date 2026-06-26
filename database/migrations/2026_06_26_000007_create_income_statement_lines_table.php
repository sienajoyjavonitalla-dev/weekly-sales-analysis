<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('income_statement_lines', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('import_batch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('uploaded_file_id')->nullable()->constrained()->nullOnDelete();
            $table->string('source_sheet')->default('Sheet');
            $table->unsignedInteger('source_row_number');
            $table->string('account_number')->nullable()->index();
            $table->string('description')->nullable()->index();
            $table->decimal('current_period_amount', 15, 2)->default(0);
            $table->decimal('current_period_percent', 8, 4)->nullable();
            $table->decimal('year_to_date_amount', 15, 2)->default(0);
            $table->decimal('year_to_date_percent', 8, 4)->nullable();
            $table->json('raw_values')->nullable();
            $table->timestamps();

            $table->unique(['uploaded_file_id', 'source_sheet', 'source_row_number'], 'income_statement_source_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('income_statement_lines');
    }
};
