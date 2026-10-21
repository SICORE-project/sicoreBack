<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $permissionId = DB::table('permissions')
            ->where('slug', 'paie.bulletins.read')
            ->value('id');

        if (! $permissionId) {
            $permissionId = DB::table('permissions')->insertGetId([
                'nom' => 'Consulter les bulletins de paie',
                'slug' => 'paie.bulletins.read',
                'groupe' => 'paie',
                'module' => 'bulletins',
                'action' => 'read',
                'est_actif' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $roleId = DB::table('roles')
            ->where('slug', 'gestionnaire_ia')
            ->value('id');

        if ($roleId) {
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
        $permissionId = DB::table('permissions')
            ->where('slug', 'paie.bulletins.read')
            ->value('id');
        $roleId = DB::table('roles')
            ->where('slug', 'gestionnaire_ia')
            ->value('id');

        if ($permissionId && $roleId) {
            DB::table('role_permission')
                ->where('role_id', $roleId)
                ->where('permission_id', $permissionId)
                ->delete();
        }
    }
};
