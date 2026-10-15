<?php

namespace Database\Seeders;

use App\Models\Admin\Role;
use App\Models\Admin\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $role = Role::query()->where('slug', 'super_admin')->first();
        if (! $role) {
            $this->call([
                TypeRoleSeeder::class,
                RoleSeeder::class,
                PermissionSeeder::class,
                RolePermissionSeeder::class,
            ]);

            $role = Role::query()->where('slug', 'super_admin')->firstOrFail();
        }

        if (User::query()->where('role_id', $role->id)->exists()) {
            return;
        }

        // Seul le compte initial permettant d'accéder au CRUD est créé.
        User::query()->firstOrCreate(['email' => 'mamadou.diop@sicore.sn'], [
            'nom' => 'Diop',
            'prenom' => 'Mamadou',
            'password' => Hash::make('password'),
            'role_id' => $role->id,
            'statut' => 'actif',
            'fonction' => 'Super Administrateur',
            'genre' => 'masculin',
            'must_change_password' => true,
            'tentatives_connexion' => 0,
        ]);
    }
}
