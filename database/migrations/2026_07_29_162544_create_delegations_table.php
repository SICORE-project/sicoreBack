<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('delegation_credits', function (Blueprint $table) {
            $table->id();
            // Informations générales
            $table->string('annee_academique');
            $table->string('reference')->unique();
            $table->string('objet');
            $table->date('date_delegation');

            // Affectation
            $table->foreignId('structure_id')
                  ->constrained('structures')
                  ->cascadeOnDelete();

            $table->foreignId('service_id')
                  ->constrained('services')
                  ->cascadeOnDelete();

            // Gestion financière
            $table->decimal('montant_disponible', 15, 2);
            $table->decimal('montant_engage', 15, 2)->default(0);
            $table->decimal('montant_consomme', 15, 2)->default(0);
            $table->decimal('solde', 15, 2)->default(0);

            // Statut
            $table->enum('statut', [
                'En attente',
                'Validée',
                'Rejetée'
            ])->default('En attente');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delegation_credits');
    }
};
