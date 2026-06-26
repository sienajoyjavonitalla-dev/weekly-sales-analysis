<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_rows', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('import_batch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('uploaded_file_id')->nullable()->constrained()->nullOnDelete();
            $table->string('source_type')->index();
            $table->string('source_sheet')->default('Sheet');
            $table->unsignedInteger('source_row_number');
            $table->string('customer_id')->nullable()->index();
            $table->string('order_number')->nullable()->index();
            $table->string('transaction_type')->nullable();
            $table->date('transaction_date')->nullable()->index();
            $table->string('item_id')->nullable()->index();
            $table->text('description')->nullable();
            $table->string('sold_to_id')->nullable()->index();
            $table->decimal('quantity_ordered', 15, 4)->nullable();
            $table->decimal('amount', 15, 2)->default(0);
            $table->string('location_id')->nullable()->index();
            $table->json('raw_values')->nullable();
            $table->timestamps();

            $table->unique(['uploaded_file_id', 'source_sheet', 'source_row_number'], 'order_rows_source_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_rows');
    }
};
