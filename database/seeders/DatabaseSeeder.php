<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Authentification uniquement : les référentiels et la paie sont
        // alimentés par les CRUD des modules fusionnés, pas par des démos.
        $this->call([
            TypeRoleSeeder::class,
            RoleSeeder::class,
            PermissionSeeder::class,
            RolePermissionSeeder::class,
            UserSeeder::class,
        ]);
    }
}
