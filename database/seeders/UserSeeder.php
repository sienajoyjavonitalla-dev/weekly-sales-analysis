<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'sjavonitalla@wagnermeters.com'],
            [
                'first_name' => 'Siena',
                'last_name' => 'Javonitalla',
                'password' => Hash::make('password'),
                'role' => 'admin',
            ]
        );
    }
}
