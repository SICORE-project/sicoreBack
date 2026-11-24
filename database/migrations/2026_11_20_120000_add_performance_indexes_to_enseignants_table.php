<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Index pour garder l'application rapide avec un très grand nombre d'enseignants.
 *
 * Presque toutes les requêtes filtrent les enseignants non supprimés (deleted_at)
 * puis comptent ou trient selon une autre colonne. Sans ces index, la base
 * parcourait toute la table à chaque comptage (mesuré : 10 s pour le tableau de
 * bord avec 1 million d'enseignants).
 */
return new class extends Migration
{
    private const INDEXES = [
        'enseignants' => [
            'ens_perf_deleted_created' => ['deleted_at', 'created_at', 'id'],
            'ens_perf_deleted_type' => ['deleted_at', 'type_engagement'],
            'ens_perf_deleted_statut' => ['deleted_at', 'statut'],
            'ens_perf_deleted_corps' => ['deleted_at', 'corps_id'],
            'ens_perf_corps_deleted' => ['corps_id', 'deleted_at'],
            'ens_perf_deleted_ia' => ['deleted_at', 'ia_id'],
            'ens_perf_deleted_nom' => ['deleted_at', 'nom', 'prenom', 'id'],
            'ens_perf_ia_deleted_nom' => ['ia_id', 'deleted_at', 'nom', 'id'],
            'ens_perf_ia_deleted_type' => ['ia_id', 'deleted_at', 'type_engagement'],
            'ens_perf_ia_deleted_ief' => ['ia_id', 'deleted_at', 'ief_id'],
            'ens_perf_ia_deleted_lieu' => ['ia_id', 'deleted_at', 'lieu_service_id'],
            'ens_perf_ia_deleted_statut' => ['ia_id', 'deleted_at', 'statut', 'est_actif'],
            'ens_perf_discipline_deleted' => ['discipline_id', 'deleted_at'],
            'ens_perf_diplome_deleted' => ['diplome_id', 'deleted_at'],
        ],
        'categories' => [
            'categories_perf_corps' => ['corps_id'],
        ],
    ];

    public function up(): void
    {
        foreach (self::INDEXES as $table => $indexes) {
            if (! Schema::hasTable($table)) {
                continue;
            }
            foreach ($indexes as $name => $columns) {
                if (Schema::hasColumns($table, $columns) && ! Schema::hasIndex($table, $name)) {
                    Schema::table($table, fn (Blueprint $blueprint) => $blueprint->index($columns, $name));
                }
            }
        }
    }

    public function down(): void
    {
        foreach (self::INDEXES as $table => $indexes) {
            if (! Schema::hasTable($table)) {
                continue;
            }
            foreach (array_keys($indexes) as $name) {
                if (Schema::hasIndex($table, $name)) {
                    Schema::table($table, fn (Blueprint $blueprint) => $blueprint->dropIndex($name));
                }
            }
        }
    }
};
