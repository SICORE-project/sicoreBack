<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Filet de sécurité pour les bases où
 * 2026_08_09_100700_create_missions_deplacement_table avait déjà tourné
 * AVANT le correctif qui y a été apporté (beneficiaire_id: users ->
 * enseignants, ajout de convocation_id) — cette migration ne recrée rien,
 * elle corrige une table déjà en place. Idempotente et silencieuse si
 * missions_deplacement n'existe pas encore (le fichier corrigé la créera
 * alors directement avec le bon schéma) ou si le correctif est déjà en
 * place (ex: table jamais migrée avant le correctif).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('missions_deplacement')) {
            return;
        }

        if (! Schema::hasColumn('missions_deplacement', 'convocation_id')) {
            Schema::table('missions_deplacement', function (Blueprint $table) {
                $table->foreignId('convocation_id')->nullable()->after('id')
                    ->constrained('convocations')->nullOnDelete();
            });
        }

        // beneficiaire_id pointe-t-il encore vers `users` ? On l'inspecte
        // via information_schema plutot que de tenter dropForeign() a
        // l'aveugle (le nom de la contrainte peut differer selon comment la
        // table a ete creee).
        $contrainte = DB::selectOne(
            "SELECT CONSTRAINT_NAME, REFERENCED_TABLE_NAME
             FROM information_schema.KEY_COLUMN_USAGE
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = 'missions_deplacement'
               AND COLUMN_NAME = 'beneficiaire_id'
               AND REFERENCED_TABLE_NAME IS NOT NULL
             LIMIT 1"
        );

        if ($contrainte && $contrainte->REFERENCED_TABLE_NAME === 'users') {
            Schema::table('missions_deplacement', function (Blueprint $table) use ($contrainte) {
                $table->dropForeign($contrainte->CONSTRAINT_NAME);
            });

            Schema::table('missions_deplacement', function (Blueprint $table) {
                $table->foreign('beneficiaire_id')->references('id')->on('enseignants')->restrictOnDelete();
            });
        }
    }

    public function down(): void
    {
        // Correctif défensif : pas de retour arrière (recréer une FK
        // fausse vers `users` n'a pas de sens).
    }
};
