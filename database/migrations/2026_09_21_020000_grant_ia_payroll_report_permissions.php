<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const PERMISSIONS = [
        ['nom' => 'Consulter les sommes perçues', 'slug' => 'paie.sommes_percues.read', 'module' => 'sommes_percues'],
        ['nom' => 'Consulter l’état des salaires', 'slug' => 'paie.etat_salaires.read', 'module' => 'etat_salaires'],
        ['nom' => 'Consulter les cotisations sociales', 'slug' => 'paie.cotisations.read', 'module' => 'cotisations'],
        ['nom' => 'Consulter les effectifs par IEF', 'slug' => 'paie.effectifs_ief.read', 'module' => 'effectifs_ief'],
        ['nom' => 'Consulter le récapitulatif par banque', 'slug' => 'paie.recap_banque.read', 'module' => 'recap_banque'],
    ];

    public function up(): void
    {
        $roleId = DB::table('roles')
            ->where('slug', 'gestionnaire_ia')
            ->value('id');

        if (! $roleId) {
            return;
        }

        foreach (self::PERMISSIONS as $permission) {
            $permissionId = DB::table('permissions')
                ->where('slug', $permission['slug'])
                ->value('id');

            if (! $permissionId) {
                $permissionId = DB::table('permissions')->insertGetId([
                    'nom' => $permission['nom'],
                    'slug' => $permission['slug'],
                    'groupe' => 'paie',
                    'module' => $permission['module'],
                    'action' => 'read',
                    'est_actif' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::table('role_permission')->updateOrInsert(
                [
                    'role_id' => $roleId,
                    'permission_id' => $permissionId,
                ],
                [
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }

    public function down(): void
    {
        $roleId = DB::table('roles')
            ->where('slug', 'gestionnaire_ia')
            ->value('id');

        if (! $roleId) {
            return;
        }

        $permissionIds = DB::table('permissions')
            ->whereIn('slug', array_column(self::PERMISSIONS, 'slug'))
            ->pluck('id');

        DB::table('role_permission')
            ->where('role_id', $roleId)
            ->whereIn('permission_id', $permissionIds)
            ->delete();
    }
};
