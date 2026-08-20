<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Ajoute la valeur 'a_completer' à l'ENUM `convocations.statut`.
 *
 * Nécessaire pour l'option A du workflow DAGE (import de fichier) : une
 * convocation importée dont il manque des informations (agent non
 * reconnu, type absent, centre non renseigné...) doit apparaître dans la
 * liste avec le statut "À compléter" (cf. exemple du point 3 du cahier
 * des charges), pour que la DAGE sache quoi corriger via le formulaire
 * (option B).
 *
 * Modification en SQL brut (DB::statement) plutôt que ->change(), qui
 * nécessiterait doctrine/dbal — absent de composer.json (cf. note dans
 * 2026_08_12_090010_add_type_convocation_id_to_convocations_table.php).
 * MySQL/MariaDB uniquement (comme le reste du schéma).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('convocations')) {
            return;
        }

        DB::statement(
            "ALTER TABLE convocations MODIFY statut ENUM('brouillon','a_completer','emise','envoyee','cloturee') NOT NULL DEFAULT 'brouillon'"
        );
    }

    public function down(): void
    {
        if (! Schema::hasTable('convocations')) {
            return;
        }

        // Repasse les lignes 'a_completer' en 'brouillon' avant de retirer
        // la valeur de l'ENUM, pour ne jamais laisser une ligne avec une
        // valeur hors-énumération après rollback.
        DB::table('convocations')->where('statut', 'a_completer')->update(['statut' => 'brouillon']);

        DB::statement(
            "ALTER TABLE convocations MODIFY statut ENUM('brouillon','emise','envoyee','cloturee') NOT NULL DEFAULT 'brouillon'"
        );
    }
};
