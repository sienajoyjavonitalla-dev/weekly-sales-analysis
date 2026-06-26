<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reconciliation_results', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('import_batch_id')->constrained()->cascadeOnDelete();
            $table->decimal('sales_analysis_total', 15, 2)->default(0);
            $table->decimal('income_statement_total', 15, 2)->default(0);
            $table->decimal('marketplace_fee_total', 15, 2)->default(0);
            $table->decimal('adjusted_income_statement_total', 15, 2)->default(0);
            $table->decimal('difference', 15, 2)->default(0);
            $table->boolean('is_balanced')->default(false)->index();
            $table->decimal('tolerance', 10, 2)->default(0);
            $table->json('category_totals')->nullable();
            $table->json('messages')->nullable();
            $table->foreignId('approved_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->unique('import_batch_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reconciliation_results');
    }
};
