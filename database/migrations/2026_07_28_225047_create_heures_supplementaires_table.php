<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('heures_supplementaires', function (Blueprint $table) {
            $table->id();

            $table->foreignId('enseignant_id')->constrained('enseignants')->onDelete('cascade');

            // Informations
            $table->integer('nb_heures');
            $table->decimal('taux_horaire', 10, 2);
            $table->decimal('montant_total', 15, 2);

            // Période (SANS contrainte)
            $table->unsignedBigInteger('periode_paie_id')->nullable();

            $table->date('date_debut');
            $table->date('date_fin');

            // Type
            $table->enum('type', ['normale', 'nuit', 'jour_ferie', 'weekend'])->default('normale');

            // Statut
            $table->enum('statut', ['en_attente', 'approuve', 'rejete'])->default('en_attente');

            // Motif
            $table->string('motif', 255)->nullable();
            $table->text('observations')->nullable();

            // Validation
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('validated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('validated_at')->nullable();

            $table->timestamps();

            $table->index('enseignant_id');
            $table->index('periode_paie_id');
            $table->index('statut');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('heures_supplementaires');
    }
};