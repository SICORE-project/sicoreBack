<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Index pour compter rapidement les fonctionnaires, contractuels et vacataires.
 *
 * Les statistiques lisent le statut de l'enseignant (statut_enseignant_id) puis,
 * à défaut, la colonne type_engagement. Sans ces index, chaque comptage parcourait
 * toute la table des enseignants (index « couvrants » : la base n'a pas besoin de lire
 * les lignes elles-mêmes).
 */
return new class extends Migration
{
    private const INDEXES = [
        'ens_eng_deleted_statut_type' => ['deleted_at', 'statut_enseignant_id', 'type_engagement'],
        'ens_eng_ia_deleted_statut_type' => ['ia_id', 'deleted_at', 'statut_enseignant_id', 'type_engagement'],
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
