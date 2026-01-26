<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RegionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $sqlFilePath = storage_path('indonesia.sql');

        if (file_exists($sqlFilePath)) {
            DB::unprepared(file_get_contents($sqlFilePath));
            $this->command->info('Region data seeded successfully.');
        } else {
            $this->command->error('SQL file not found: ' . $sqlFilePath);
        }
    }
}
