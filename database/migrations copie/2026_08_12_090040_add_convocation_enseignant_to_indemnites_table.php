<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ajoute la traçabilité manquante entre une indemnité et son origine
 * (convocation + enseignant). Sans ça, aucune résolution automatique de
 * barème n'est possible : `calculer()`/`simuler()` ne savent pas pour
 * quelle convocation ni quel enseignant l'indemnité est calculée.
 *
 * Nullable : une indemnité peut continuer à être créée manuellement
 * (comme aujourd'hui) sans convocation associée. C'est un enrichissement,
 * pas une contrainte bloquante sur l'existant.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('indemnites', 'convocation_id')) {
            return;
        }

        Schema::table('indemnites', function (Blueprint $table) {
            $table->foreignId('convocation_id')
                ->nullable()
                ->after('utilisateur_id')
                ->constrained('convocations')
                ->nullOnDelete();

            $table->foreignId('enseignant_id')
                ->nullable()
                ->after('convocation_id')
                ->constrained('enseignants')
                ->nullOnDelete();

            $table->index('convocation_id');
            $table->index('enseignant_id');
        });
    }

    public function down(): void
    {
        Schema::table('indemnites', function (Blueprint $table) {
            $table->dropForeign(['convocation_id']);
            $table->dropForeign(['enseignant_id']);
            $table->dropColumn(['convocation_id', 'enseignant_id']);
        });
    }
};
