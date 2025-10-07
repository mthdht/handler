<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Organisation;
use App\Models\Etablissement;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        $mth = User::factory()
            ->has(Organisation::factory(3)
                ->has(Etablissement::factory(5)))
            ->create([
                'name' => 'Mth',
                'email' => 'email@gmail.com',
            ]);
    }
}
