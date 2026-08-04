<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Admin\Role;
use App\Models\Admin\Permission;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Récupérer les rôles par slug
        $superAdmin = Role::where('slug', 'super_admin')->first();
        $admin = Role::where('slug', 'admin')->first();

        // Récupérer toutes les permissions
        $allPermissions = Permission::pluck('id')->toArray();

        // SUPER ADMIN : toutes les permissions
        if ($superAdmin) {
            $superAdmin->permissions()->sync($allPermissions);
        }

        // ADMIN
        if ($admin) {
            $adminPermissions = Permission::whereIn('structure', ['administration', 'parametrage', 'enseignants'])->pluck('id')->toArray();
            $admin->permissions()->sync($adminPermissions);
        }
    }
}