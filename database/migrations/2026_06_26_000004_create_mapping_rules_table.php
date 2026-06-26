<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mapping_rules', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('product_category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('source_type')->default('sales_analysis')->index();
            $table->string('match_field')->index();
            $table->string('match_operator')->default('starts_with');
            $table->string('pattern');
            $table->string('target_bucket')->nullable()->index();
            $table->unsignedSmallInteger('priority')->default(100)->index();
            $table->boolean('is_active')->default(true)->index();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['source_type', 'match_field', 'match_operator']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mapping_rules');
    }
};
