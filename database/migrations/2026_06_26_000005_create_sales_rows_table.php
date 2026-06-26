<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_rows', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('import_batch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('uploaded_file_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('product_category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('source_sheet');
            $table->unsignedInteger('source_row_number');
            $table->string('source_bucket')->default('raw')->index();
            $table->string('item_id')->nullable()->index();
            $table->text('description')->nullable();
            $table->string('customer_id')->nullable()->index();
            $table->string('customer_name')->nullable();
            $table->string('invoice_number')->nullable()->index();
            $table->string('sales_rep_id')->nullable()->index();
            $table->string('country')->nullable();
            $table->string('bill_to_state')->nullable();
            $table->date('invoice_date')->nullable()->index();
            $table->decimal('quantity_ordered', 15, 4)->default(0);
            $table->decimal('amount', 15, 2)->default(0);
            $table->string('classification_status')->default('unmatched')->index();
            $table->json('raw_values')->nullable();
            $table->timestamps();

            $table->unique(['uploaded_file_id', 'source_sheet', 'source_row_number'], 'sales_rows_source_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_rows');
    }
};
