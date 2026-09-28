<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('weekly_meter_additional_fees', function (Blueprint $table): void {
            $table->id();
            $table->unsignedSmallInteger('year')->index();
            $table->unsignedTinyInteger('month')->index();
            $table->string('model_label')->index();
            $table->decimal('amount', 15, 2);
            $table->unsignedInteger('sort_order')->default(0);
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['year', 'month', 'model_label'], 'wm_fees_period_model_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('weekly_meter_additional_fees');
    }
};
