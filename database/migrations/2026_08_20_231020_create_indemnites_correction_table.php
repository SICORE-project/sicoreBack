<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Indemnité de correction : montant = nombre_copies x taux_copie, pour un
 * membre ayant la fonction "Correction" sur UN métier précis d'UN centre
 * d'une convocation (le taux varie librement par métier, saisi à la volée,
 * pas de barème préconfiguré — demande utilisatrice explicite). Pas de
 * "fiche" à remplir (contrairement à missions_deplacement) : un simple
 * enregistrement calculé.
 *
 * Table dédiée plutôt que la table générique `indemnites` (mode_calcul
 * forfaitaire/horaire/kilometrique, resolue via un barème type_indemnites) :
 * ce moteur générique n'est utilisé nulle part ailleurs dans l'app (même
 * Frais de déplacement, la fonctionnalité la plus aboutie, a sa propre
 * table plutôt que de passer par lui), et son système de barème ne modélise
 * pas un taux variable par métier sans détour artificiel.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('indemnites_correction', function (Blueprint $table) {
            $table->id();

            $table->foreignId('convocation_id')->constrained('convocations')->cascadeOnDelete();
            $table->foreignId('convocation_centre_id')->constrained('convocation_centres')->cascadeOnDelete();
            $table->foreignId('enseignant_id')->constrained('enseignants')->restrictOnDelete();

            $table->string('metier');
            $table->unsignedInteger('nombre_copies');
            $table->decimal('taux_copie', 12, 2);
            $table->decimal('montant', 12, 2);

            $table->enum('statut', ['calcule', 'valide', 'rejete'])->default('calcule');

            $table->timestamps();

            // Un correcteur ne peut avoir qu'une seule indemnité de
            // correction par métier, pour une même convocation (évite les
            // doublons si le calcul groupé est soumis deux fois).
            $table->unique(['convocation_id', 'enseignant_id', 'metier'], 'indemnites_correction_unique_membre_metier');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('indemnites_correction');
    }
};
