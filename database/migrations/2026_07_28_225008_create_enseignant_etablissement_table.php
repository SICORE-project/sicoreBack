<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Table de liaison enseignant - établissement
        Schema::create('enseignant_etablissement', function (Blueprint $table) {
            $table->id();

            $table->foreignId('enseignant_id')->constrained('enseignants')->onDelete('cascade');
            $table->foreignId('centre_formation_id')->constrained('centres_formation')->onDelete('cascade');
            $table->foreignId('lieu_service_id')->nullable()->constrained('lieu_de_services')->onDelete('set null');

            // Date d'affectation
            $table->date('date_affectation')->nullable();
            $table->date('date_fin')->nullable();

            // Statut
            $table->boolean('est_actif')->default(true);

            $table->timestamps();

            $table->unique(['enseignant_id', 'centre_formation_id']);
            $table->index('centre_formation_id');
            $table->index('est_actif');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('enseignant_etablissement');
    }
};