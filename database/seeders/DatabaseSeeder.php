<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Organisation;
use App\Models\Etablissement;

use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        $adminRole = Role::create(['name' => 'admin']);
        $recruteurRole = Role::create(['name' => 'recruteur']);
        $candidatRole = Role::create(['name' => 'candidat']);

        $manageOrga = Permission::create(['name' => 'manage organisations']);
        $manageEtablissement = Permission::create(['name' => 'manage etablissements']);

        $adminRole->givePermissionTo($manageOrga);
        $adminRole->givePermissionTo($manageEtablissement);

        $mth = User::factory()
            ->has(Organisation::factory(1)
                ->has(Etablissement::factory(2)))
            ->create([
                'name' => 'Mth',
                'email' => 'mth@gmail.com',
            ]);
            
            
        $max = User::factory()
            ->has(Organisation::factory(1)
            ->has(Etablissement::factory(2)))
            ->create([
                'name' => 'Max',
                'email' => 'max@gmail.com',
            ]);
            
        $eric = User::factory()
            ->has(Organisation::factory(1)
                ->has(Etablissement::factory(2)))
                ->create([
                    'name' => 'Eric',
                    'email' => 'eric@gmail.com',
                ]);
        
        
        $mth->assignRole($adminRole);
    }
}
