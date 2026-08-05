<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('impots_revenu', function (Blueprint $table) {
            $table->id();

            // Tranche d'imposition
            $table->decimal('tranche_min', 15, 2);
            $table->decimal('tranche_max', 15, 2)->nullable();
            $table->decimal('taux', 8, 2); // Taux en pourcentage

            // Montant fixe à déduire
            $table->decimal('montant_fixe', 15, 2)->nullable();

            // Période de validité
            $table->year('annee')->nullable();
            $table->date('date_debut')->nullable();
            $table->date('date_fin')->nullable();

            $table->boolean('est_actif')->default(true);
            $table->timestamps();

            $table->index('annee');
            $table->index('est_actif');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('impots_revenu');
    }
};