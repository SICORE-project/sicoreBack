<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Retire la valeur 'cloturee' de l'ENUM `convocations.statut`.
 *
 * Demande utilisatrice : le statut "Clôturée" n'a aucune action de
 * fermeture réelle nulle part dans l'application (contrairement aux
 * fiches de déplacement, qui ont un vrai bouton "Clôturer") — c'était un
 * simple choix de formulaire jamais déclenché automatiquement. Retiré du
 * cycle de vie plutôt que laissé comme statut mort.
 *
 * Modification en SQL brut (DB::statement) plutôt que ->change(), qui
 * nécessiterait doctrine/dbal — voir
 * 2026_08_12_100100_add_a_completer_to_convocations_statut_enum.php pour
 * le même principe.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('convocations')) {
            return;
        }

        // Repasse les lignes 'cloturee' en 'envoyee' avant de retirer la
        // valeur de l'ENUM, pour ne jamais laisser une ligne avec une
        // valeur hors-énumération après la migration.
        DB::table('convocations')->where('statut', 'cloturee')->update(['statut' => 'envoyee']);

        DB::statement(
            "ALTER TABLE convocations MODIFY statut ENUM('brouillon','a_completer','emise','envoyee') NOT NULL DEFAULT 'brouillon'"
        );
    }

    public function down(): void
    {
        if (! Schema::hasTable('convocations')) {
            return;
        }

        DB::statement(
            "ALTER TABLE convocations MODIFY statut ENUM('brouillon','a_completer','emise','envoyee','cloturee') NOT NULL DEFAULT 'brouillon'"
        );
    }
};
