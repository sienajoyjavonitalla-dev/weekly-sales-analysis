<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            RhpProductCategorySeeder::class,
            PartsTsdProductCategorySeeder::class,
            RhpMappingRuleSeeder::class,
            PartsTsdMappingRuleSeeder::class,
            StateMappingRuleSeeder::class,
        ]);
    }
}
