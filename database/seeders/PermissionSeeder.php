<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\Admin\Permission;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [

            // Administration
            'administration.users.read',
            'administration.users.create',
            'administration.users.update',
            'administration.users.delete',

            'administration.roles.read',
            'administration.roles.create',
            'administration.roles.update',
            'administration.roles.delete',

            'administration.permissions.read',
            'administration.permissions.create',
            'administration.permissions.update',
            'administration.permissions.delete',

            // Paramétrage
            'parametrage.read',
            'parametrage.write',

            // Personnel
            'personnel.read',
            'personnel.create',
            'personnel.update',
            'personnel.delete',

            // Paie
            'paie.read',
            'paie.generate',
            'paie.validate',

            // Indemnités
            'indemnites.read',
            'indemnites.validate',

            // Rapports
            'rapports.read',

            // Tableau de bord
            'dashboard.view',
        ];

        foreach ($permissions as $permission) {

            Permission::firstOrCreate([
                'slug' => $permission
            ],[
                'nom' => ucwords(str_replace('.', ' ', $permission))
            ]);

        }
    }
}