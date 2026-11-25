<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/*
 * L'administrateur (agent de la DAGE) n'a ni la partie technique ni les suppressions :
 * elles sont réservées au super administrateur.
 * (permissions:sync ajoute des permissions mais n'en retire jamais.)
 */
return new class extends Migration
{
    private const SLUGS = [
        'administration.roles.manage',
        'administration.permissions.manage',
        'parametrage.lieux_service.manage',
        'administration.users.delete',
        'enseignants.delete',
        'parametrage.ia.delete',
        'parametrage.ief.delete',
    ];

    public function up(): void
    {
        $roleIds = DB::table('roles')->whereIn('slug', ['admin', 'administrateur'])->pluck('id');
        $permissionIds = DB::table('permissions')->whereIn('slug', self::SLUGS)->pluck('id');

        if ($roleIds->isEmpty() || $permissionIds->isEmpty()) {
            return;
        }

        DB::table('role_permission')
            ->whereIn('role_id', $roleIds)
            ->whereIn('permission_id', $permissionIds)
            ->delete();
    }

    public function down(): void
    {
        // Rien à restaurer : ces permissions ne doivent pas revenir à l'administrateur.
    }
};
