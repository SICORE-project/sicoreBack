<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $typeRoleId = DB::table('type_roles')
            ->where('code', 'gestion')
            ->value('id');

        if (! $typeRoleId) {
            return;
        }

        DB::table('roles')->updateOrInsert(
            ['slug' => 'decpc'],
            [
                'nom' => 'DECPC',
                'description' => 'Direction des Examens, Concours Professionnels et Certifications',
                'type_role_id' => $typeRoleId,
                'est_actif' => true,
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );
    }

    public function down(): void
    {
        DB::table('roles')->where('slug', 'decpc')->delete();
    }
};
