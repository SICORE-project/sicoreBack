<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ajoute une catégorie de personnel dédiée, distincte de statut_enseignant_id.
 *
 * IMPORTANT : ne pas confondre avec `statuts_enseignant.categorie`, qui
 * documente un état administratif (actif/inactif/temporaire — cf.
 * 2026_07_28_222517_create_statuts_enseignant_table.php), pas un régime
 * d'emploi. D'où une colonne séparée, sans ambiguïté.
 *
 * Nullable au départ : aucune donnée existante ne permet de déduire la
 * valeur automatiquement (aucune table ne portait cette info). A remplir
 * lors d'une campagne de qualification des fiches enseignant, ou à défaut
 * par défaut 'vacataire' si c'est la majorité de la base actuelle
 * (à confirmer avec l'équipe métier avant d'appliquer un backfill).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('enseignants', 'categorie_personnel')) {
            return;
        }

        Schema::table('enseignants', function (Blueprint $table) {
            $table->enum('categorie_personnel', ['vacataire', 'contractuel', 'fonctionnaire'])
                ->nullable()
                ->after('statut_enseignant_id');

            $table->index('categorie_personnel');
        });
    }

    public function down(): void
    {
        Schema::table('enseignants', function (Blueprint $table) {
            $table->dropIndex(['categorie_personnel']);
            $table->dropColumn('categorie_personnel');
        });
    }
};
