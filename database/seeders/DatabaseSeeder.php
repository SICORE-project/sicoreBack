<?php

namespace Database\Seeders;

use App\Models\Admin\Role;
use App\Models\Admin\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Création du rôle Administrateur
        // $role = Role::firstOrCreate(
        //     ['slug' => 'administrateur'],
        //     [
        //         'nom' => 'Administrateur',
        //         'description' => 'Administrateur du système',
        //         'niveau' => 1,
        //         'est_actif' => true,
        //     ]
        // );

        // Création du compte administrateur
        // \App\Models\Admin\User::firstOrCreate(
        //     ['email' => 'adminsicore@yopmail.com'],
        //     [
        //         'nom' => 'Administrateur',
        //         'prenom' => 'SICORE',
        //         'password' => Hash::make('Admin@123456'),
        //         'role_id' => $role->id,
        //     ]
        // );

        // Appel des seeders pour les rôles et permissions
        $this->call([
            RegionSeeder::class,
            TypeRoleSeeder::class,
            RoleSeeder::class,
            PermissionSeeder::class,
            RolePermissionSeeder::class,
            IaSeeder::class,
            IefSeeder::class,
            LieuServiceSeeder::class,
            UserSeeder::class,
            InstitutFinancierSeeder::class,
        ]);

    }

    /**
     * Créer un utilisateur administrateur par défaut
     */
    private function createAdminUser(): void
    {
        $adminRole = Role::where('slug', 'admin')->first();

        if ($adminRole) {
            User::firstOrCreate(
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
