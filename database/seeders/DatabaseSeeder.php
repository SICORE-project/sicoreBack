<?php

namespace Database\Seeders;

use App\Models\Admin\Role;
use App\Models\Admin\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Création du rôle Administrateur
        $role = Role::firstOrCreate(
            ['slug' => 'administrateur'],
            [
                'nom' => 'Administrateur',
                'description' => 'Administrateur du système',
                'niveau' => 1,
                'est_actif' => true,
            ]
        );

        // Création du compte administrateur
        \App\Models\Admin\User::firstOrCreate(
            ['email' => 'adminsicore@yopmail.com'],
            [
                'nom' => 'Administrateur',
                'prenom' => 'SICORE',
                'password' => Hash::make('Admin@123456'),
                'role_id' => $role->id,
            ]
        );

        // Appel des seeders pour les rôles et permissions
        $this->call([
        RoleSeeder::class,
        PermissionSeeder::class,
        RolePermissionSeeder::class,
    ]);

    }
}
