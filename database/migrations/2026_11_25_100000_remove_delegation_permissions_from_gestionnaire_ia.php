<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/*
 * Les délégations de crédits sont réservées à l'administration centrale :
 * le gestionnaire IA ne doit pas avoir ces permissions.
 * (permissions:sync ajoute des permissions mais n'en retire jamais.)
 */
return new class extends Migration
{
    private const SLUGS = [
        'paie.delegation-credits.read',
        'paie.delegation-credits.update',
        'paie.edition-delegation.read',
        'paie.edition-engagement.read',
        'paie.projection.read',
    ];

    public function up(): void
    {
        $roleIds = DB::table('roles')->where('slug', 'gestionnaire_ia')->pluck('id');
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
        // Rien à restaurer : ces permissions ne doivent pas revenir au gestionnaire IA.
    }
};
