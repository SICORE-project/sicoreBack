<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Index pour compter rapidement les fonctionnaires, contractuels et vacataires.
 *
 * Les statistiques lisent le statut de l'enseignant (statut_enseignant_id) puis,
 * à défaut, la colonne type_engagement. Sans ces index, chaque comptage parcourait
 * toute la table des enseignants.
 *
 * statut_enseignant_id est placé avant deleted_at : ainsi ces index ne remplacent pas
 * ceux des autres requêtes (« ia_id + deleted_at + ... »), qui restent plus adaptés.
 */
return new class extends Migration
{
    private const INDEXES = [
        'ens_eng_statut_deleted_type' => ['statut_enseignant_id', 'deleted_at', 'type_engagement'],
        'ens_eng_ia_statut_deleted_type' => ['ia_id', 'statut_enseignant_id', 'deleted_at', 'type_engagement'],
    ];

    public function up(): void
    {
        if (! Schema::hasTable('enseignants')) {
            return;
        }
        foreach (self::INDEXES as $name => $columns) {
            if (Schema::hasColumns('enseignants', $columns) && ! Schema::hasIndex('enseignants', $name)) {
                Schema::table('enseignants', fn (Blueprint $table) => $table->index($columns, $name));
            }
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('enseignants')) {
            return;
        }
        foreach (array_keys(self::INDEXES) as $name) {
            if (Schema::hasIndex('enseignants', $name)) {
                Schema::table('enseignants', fn (Blueprint $table) => $table->dropIndex($name));
            }
        }
    }
};
