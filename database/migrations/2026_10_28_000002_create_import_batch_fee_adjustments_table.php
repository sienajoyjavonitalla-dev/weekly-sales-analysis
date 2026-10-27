<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('import_batch_fee_adjustments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('import_batch_id')->constrained()->cascadeOnDelete();
            $table->string('marketplace')->index();
            $table->decimal('amount', 15, 2);
            $table->unsignedInteger('sort_order')->default(0);
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['import_batch_id', 'marketplace'], 'batch_fee_adj_batch_marketplace_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('import_batch_fee_adjustments');
    }
};
