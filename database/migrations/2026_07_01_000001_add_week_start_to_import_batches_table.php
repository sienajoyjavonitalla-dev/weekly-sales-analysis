<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('import_batches', function (Blueprint $table): void {
            $table->date('week_start')->nullable()->after('id');
        });

        $batches = DB::table('import_batches')
            ->whereNotNull('week_ending')
            ->get(['id', 'week_ending']);

        foreach ($batches as $batch) {
            DB::table('import_batches')
                ->where('id', $batch->id)
                ->update([
                    'week_start' => date('Y-m-d', strtotime($batch->week_ending.' -6 days')),
                ]);
        }
    }

    public function down(): void
    {
        Schema::table('import_batches', function (Blueprint $table): void {
            $table->dropColumn('week_start');
        });
    }
};
