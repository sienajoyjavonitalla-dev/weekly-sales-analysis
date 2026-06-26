<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('report_totals', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('import_batch_id')->constrained()->cascadeOnDelete();
            $table->string('report_type')->index();
            $table->string('period_label')->nullable()->index();
            $table->string('metric_key')->index();
            $table->decimal('quantity', 15, 4)->nullable();
            $table->decimal('amount', 15, 2)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['import_batch_id', 'report_type', 'metric_key'], 'report_totals_metric_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_totals');
    }
};
