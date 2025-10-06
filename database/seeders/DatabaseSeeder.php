<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Organisation;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        $mth = User::factory()
            ->has(Organisation::factory()->count(3))
            ->create([
                'name' => 'Mth',
                'email' => 'email@gmail.com',
            ]);
    }
}
