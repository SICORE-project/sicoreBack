<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('enseignant_mutuelle', function (Blueprint $table) {
            $table->id();

            // Enseignant concerné
            $table->foreignId('enseignant_id')
                ->constrained('enseignants')
                ->cascadeOnDelete();

            // Mutuelle
            $table->foreignId('mutuelle_id')
                ->constrained('mutuelles')
                ->cascadeOnDelete();

            // Numéro fourni par la mutuelle
            $table->string('numero_affiliation', 50)
                ->nullable();

            // Début de l'adhésion
            $table->date('date_adhesion')
                ->nullable();

            // Fin éventuelle de l'adhésion
            $table->date('date_resiliation')
                ->nullable();

            $table->boolean('est_actif')
                ->default(true);

            $table->timestamps();

            $table->unique([
                'enseignant_id',
                'mutuelle_id',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('enseignant_mutuelle');
    }
};