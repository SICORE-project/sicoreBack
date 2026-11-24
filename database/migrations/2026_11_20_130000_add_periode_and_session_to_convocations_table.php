<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Le modele Convocations (fillable/casts) et le formulaire de convocation
 * utilisent date_debut, date_fin, heure_debut et session, mais aucune
 * migration ne les creait : l'enregistrement d'une convocation echouait
 * ("Champ 'date_debut' inconnu"), et les filtres par session aussi.
 *
 * Colonnes nullables : les convocations deja en base n'ont pas ces valeurs
 * (le formulaire, lui, les exige pour toute nouvelle convocation).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('convocations', function (Blueprint $table) {
            if (! Schema::hasColumn('convocations', 'date_debut')) {
                $table->date('date_debut')->nullable()->after('date_emission');
            }

            if (! Schema::hasColumn('convocations', 'date_fin')) {
                $table->date('date_fin')->nullable()->after('date_debut');
            }

            if (! Schema::hasColumn('convocations', 'heure_debut')) {
                $table->time('heure_debut')->nullable()->after('date_fin');
            }

            if (! Schema::hasColumn('convocations', 'session')) {
                $table->string('session', 150)->nullable()->after('objet');
                $table->index('session');
            }
        });
    }

    public function down(): void
    {
        Schema::table('convocations', function (Blueprint $table) {
            if (Schema::hasColumn('convocations', 'session')) {
                $table->dropIndex(['session']);
                $table->dropColumn('session');
            }

            foreach (['heure_debut', 'date_fin', 'date_debut'] as $colonne) {
                if (Schema::hasColumn('convocations', $colonne)) {
                    $table->dropColumn($colonne);
                }
            }
        });
    }
};
