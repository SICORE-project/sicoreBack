<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parametres_impots', function (Blueprint $table) {
            $table->id();

            $table->year('annee')->unique();

            // Abattement général
            $table->decimal('abattement_general', 15, 2)->nullable();

            // Plafond des cotisations
            $table->decimal('plafond_cnss', 15, 2)->nullable();
            $table->decimal('taux_cnss', 8, 2)->nullable();

            // Impôt sur le revenu
            $table->decimal('taux_impot_min', 8, 2)->nullable();
            $table->decimal('taux_impot_max', 8, 2)->nullable();

            // Seuils d'exonération
            $table->decimal('seuil_exoneration', 15, 2)->nullable();

            $table->boolean('est_actif')->default(true);
            $table->timestamps();

            $table->index('annee');
            $table->index('est_actif');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parametres_impots');
    }
};