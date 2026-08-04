<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            PermissionSeeder::class,
            RoleSeeder::class,
            RolePermissionSeeder::class,
        ]);

        // Optionnel : Créer un utilisateur admin par défaut
        $this->createAdminUser();
    }

    /**
     * Créer un utilisateur administrateur par défaut
     */
    private function createAdminUser(): void
    {
        $adminRole = \App\Models\Admin\Role::where('slug', 'admin')->first();

        if ($adminRole) {
            \App\Models\Admin\User::firstOrCreate(
                ['email' => 'adminsicore@yopmail.com'],
                [
                    'nom' => 'Admin',
                    'prenom' => 'SICORE',
                    'email' => 'adminsicore@yopmail.com',
                    'password' => bcrypt('password'),
                    'role_id' => $adminRole->id,
                    'statut' => 'actif',
                    'fonction' => 'Administrateur',
                ]
            );
        }
    }
}