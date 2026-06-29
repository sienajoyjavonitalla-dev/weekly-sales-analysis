<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            if (! Schema::hasColumn('users', 'first_name')) {
                $table->string('first_name')->nullable()->after('id');
            }

            if (! Schema::hasColumn('users', 'last_name')) {
                $table->string('last_name')->nullable()->after('first_name');
            }
        });

        if (Schema::hasColumn('users', 'name')) {
            DB::table('users')
                ->select(['id', 'name'])
                ->orderBy('id')
                ->chunkById(100, function ($users): void {
                    foreach ($users as $user) {
                        [$firstName, $lastName] = $this->splitName((string) $user->name);

                        DB::table('users')
                            ->where('id', $user->id)
                            ->update([
                                'first_name' => $firstName,
                                'last_name' => $lastName,
                            ]);
                    }
                });

            Schema::table('users', function (Blueprint $table): void {
                $table->dropColumn('name');
            });
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            if (! Schema::hasColumn('users', 'name')) {
                $table->string('name')->nullable()->after('id');
            }
        });

        DB::table('users')->orderBy('id')->chunkById(100, function ($users): void {
            foreach ($users as $user) {
                DB::table('users')
                    ->where('id', $user->id)
                    ->update([
                        'name' => trim($user->first_name.' '.$user->last_name),
                    ]);
            }
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn(['first_name', 'last_name']);
        });
    }

    /**
     * @return array{0:string, 1:string}
     */
    private function splitName(string $name): array
    {
        $parts = preg_split('/\s+/', trim($name), 2) ?: [];

        return [
            $parts[0] ?? 'User',
            $parts[1] ?? '',
        ];
    }
};
