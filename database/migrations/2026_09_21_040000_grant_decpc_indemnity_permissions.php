<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const PERMISSIONS = [
        ['nom' => 'Consulter les indemnités', 'slug' => 'indemnites.read', 'module' => 'indemnites', 'action' => 'read'],
        ['nom' => 'Gérer les indemnités', 'slug' => 'indemnites.manage', 'module' => 'indemnites', 'action' => 'manage'],
        ['nom' => 'Valider les indemnités', 'slug' => 'indemnites.validate', 'module' => 'indemnites', 'action' => 'validate'],
    ];

    public function up(): void
    {
        $roleId = DB::table('roles')->where('slug', 'decpc')->value('id');

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
                    'groupe' => 'indemnites',
                    'module' => $permission['module'],
                    'action' => $permission['action'],
                    'est_actif' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::table('role_permission')->updateOrInsert(
                ['role_id' => $roleId, 'permission_id' => $permissionId],
                ['created_at' => now(), 'updated_at' => now()]
            );
        }
    }

    public function down(): void
    {
        $roleId = DB::table('roles')->where('slug', 'decpc')->value('id');
        $permissionIds = DB::table('permissions')
            ->whereIn('slug', array_column(self::PERMISSIONS, 'slug'))
            ->pluck('id');

        if ($roleId) {
            DB::table('role_permission')
                ->where('role_id', $roleId)
                ->whereIn('permission_id', $permissionIds)
                ->delete();
        }
    }
};
