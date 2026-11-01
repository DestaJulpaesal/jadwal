<?php

namespace Database\Seeders;

use App\Models\TaskStatus;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        foreach (['Belum dimulai', 'Sedang dikerjakan', 'Selesai'] as $name) {
            TaskStatus::firstOrCreate(
                ['name' => $name],
                ['is_completed' => $name === 'Selesai']
            );
        }

        if (app()->environment('local')) {
            $this->call(LocalLifeHubSeeder::class);
        }
    }
}
