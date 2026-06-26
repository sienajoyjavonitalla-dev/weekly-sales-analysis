<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_rows', function (Blueprint $table): void {
            $table->foreignId('mapping_rule_id')->nullable()->after('product_category_id')->constrained()->nullOnDelete();
            $table->foreignId('classified_by_user_id')->nullable()->after('classification_status')->constrained('users')->nullOnDelete();
            $table->timestamp('classified_at')->nullable()->after('classified_by_user_id');
            $table->text('classification_notes')->nullable()->after('classified_at');
        });
    }

    public function down(): void
    {
        Schema::table('sales_rows', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('mapping_rule_id');
            $table->dropConstrainedForeignId('classified_by_user_id');
            $table->dropColumn(['classified_at', 'classification_notes']);
        });
    }
};
