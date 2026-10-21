<?php

namespace Database\Seeders;

use App\Models\Admin\Role;
use App\Models\Admin\User;
use App\Models\Parametrage\LieuService;  // ✅ IMPORT AJOUTÉ
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

        // Créer les utilisateurs
        foreach ($users as $userData) {
            User::query()->firstOrCreate(
                ['email' => $userData['email']],
                $userData
            );
        }
    }
}