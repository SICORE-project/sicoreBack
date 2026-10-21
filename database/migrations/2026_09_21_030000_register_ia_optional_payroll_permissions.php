<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        foreach ([
            ['nom' => 'Exporter les bulletins de paie', 'slug' => 'paie.bulletins.export', 'module' => 'bulletins', 'action' => 'export'],
            ['nom' => 'Consulter la masse salariale', 'slug' => 'paie.masse_salariale.read', 'module' => 'masse_salariale', 'action' => 'read'],
        ] as $permission) {
            if (! DB::table('permissions')->where('slug', $permission['slug'])->exists()) {
                DB::table('permissions')->insert($permission + [
                    'groupe' => 'paie', 'est_actif' => true, 'created_at' => now(), 'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        // Conserver les permissions : elles peuvent avoir été attribuées par un administrateur.
    }
};
