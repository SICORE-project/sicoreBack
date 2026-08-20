<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Correctif : empêche qu'une même indemnité validée soit incluse dans
 * deux états de paie différents (double paiement).
 *
 * `etat_paie_indemnite_id` reste NULL tant que l'indemnité n'a pas été
 * "consommée" par une génération d'état de paie. Une fois remplie,
 * EtatPaieIndemnitesController::generer() doit l'exclure des prochaines
 * générations (voir le filtre whereNull() ajouté au contrôleur).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('indemnites', function (Blueprint $table) {
            $table->foreignId('etat_paie_indemnite_id')
                ->nullable()
                ->after('type_indemnite_id')
                ->constrained('etat_paie_indemnites')
                ->nullOnDelete();

            $table->index('etat_paie_indemnite_id');
        });
    }

    public function down(): void
    {
        Schema::table('indemnites', function (Blueprint $table) {
            $table->dropConstrainedForeignId('etat_paie_indemnite_id');
        });
    }
};
