<?php

namespace Database\Seeders;

use App\Models\Admin\Permission;
use App\Models\Admin\Role;
//use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AccuseReceptionPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            [
                'nom' => 'Consulter les accusés de réception',
                'slug' => 'accuses_reception.view',
                'groupe' => 'indemnites',
                'module' => 'accuses_reception',
                'action' => 'view',
                'description' => 'Consulter les accusés de réception',
                'est_actif' => true,
            ],
            [
                'nom' => 'Créer un accusé de réception',
                'slug' => 'accuses_reception.create',
                'groupe' => 'indemnites',
                'module' => 'accuses_reception',
                'action' => 'create',
                'description' => 'Créer un accusé de réception',
                'est_actif' => true,
            ],
            [
                'nom' => 'Modifier un accusé de réception',
                'slug' => 'accuses_reception.update',
                'groupe' => 'indemnites',
                'module' => 'accuses_reception',
                'action' => 'update',
                'description' => 'Modifier un accusé de réception',
                'est_actif' => true,
            ],
            [
                'nom' => 'Supprimer un accusé de réception',
                'slug' => 'accuses_reception.delete',
                'groupe' => 'indemnites',
                'module' => 'accuses_reception',
                'action' => 'delete',
                'description' => 'Supprimer un accusé de réception',
                'est_actif' => true,
            ],
            [
                'nom' => 'Exporter les accusés de réception',
                'slug' => 'accuses_reception.export',
                'groupe' => 'indemnites',
                'module' => 'accuses_reception',
                'action' => 'export',
                'description' => 'Exporter les accusés de réception',
                'est_actif' => true,
            ],
        ];

        foreach ($permissions as $permission) {
            Permission::updateOrCreate(
                ['slug' => $permission['slug']],
                $permission
            );
        }

        $role = Role::where('slug', 'super_admin')->firstOrFail();

        $permissionIds = Permission::whereIn(
            'slug',
            array_column($permissions, 'slug')
        )->pluck('id');

        $role->permissions()->syncWithoutDetaching($permissionIds);
    }
}
