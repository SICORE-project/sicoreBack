<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('enseignant_syndicat', function (Blueprint $table) {
            $table->id();

            // Enseignant concerné
            $table->foreignId('enseignant_id')
                ->constrained('enseignants')
                ->cascadeOnDelete();

            // Syndicat auquel l'enseignant adhère
            $table->foreignId('syndicat_id')
                ->constrained('syndicats')
                ->cascadeOnDelete();

            // Permet éventuellement d'avoir un taux
            // différent du taux standard du syndicat
            $table->decimal('taux_personnalise', 8, 2)
                ->nullable();

            // Informations d'adhésion
            $table->date('date_adhesion')
                ->nullable();

            $table->date('date_resiliation')
                ->nullable();

            $table->string('numero_affiliation', 50)
                ->nullable();

            $table->boolean('est_actif')
                ->default(true);

            $table->timestamps();

            // Un enseignant ne doit pas avoir deux fois
            // le même syndicat.
            $table->unique([
                'enseignant_id',
                'syndicat_id',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('enseignant_syndicat');
    }
};