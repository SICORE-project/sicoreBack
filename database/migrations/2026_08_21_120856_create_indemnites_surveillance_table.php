<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('indemnites_surveillance', function (Blueprint $table) {
            $table->id();

            $table->foreignId('convocation_id')->constrained('convocations')->cascadeOnDelete();
            $table->foreignId('convocation_centre_id')->constrained('convocation_centres')->cascadeOnDelete();
            $table->foreignId('enseignant_id')->constrained('enseignants')->restrictOnDelete();

            $table->string('metier');
            $table->decimal('nombre_heures', 6, 2);
            $table->decimal('tarif_horaire', 12, 2);
            $table->decimal('montant', 12, 2);

            $table->enum('statut', ['calcule', 'valide', 'rejete'])->default('calcule');

            $table->timestamps();

            $table->unique(['convocation_id', 'enseignant_id', 'metier'], 'indemnites_surveillance_unique_membre_metier');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('indemnites_surveillance');
    }
};
