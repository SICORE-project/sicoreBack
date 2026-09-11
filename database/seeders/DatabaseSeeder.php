<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RegionSeeder::class,
            TypeRoleSeeder::class,
            RoleSeeder::class,
            PermissionSeeder::class,
            RolePermissionSeeder::class,
            IaSeeder::class,
            IefSeeder::class,
            UserSeeder::class,
            GestionPaieSeeder::class,
        ]);
    }
}