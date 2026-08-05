<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mise_a_jour_pc_vac', function (Blueprint $table) {
            $table->id();

            $table->foreignId('enseignant_id')->constrained('enseignants')->onDelete('cascade');

            // Type
            $table->enum('type', ['pc', 'vac', 'heures_supplementaires', 'principal_interimaire'])->default('pc');

            // Informations
            $table->string('libelle', 100);
            $table->decimal('montant', 15, 2)->nullable();
            $table->integer('heures')->nullable();
            $table->decimal('taux_horaire', 10, 2)->nullable();

            // Période (SANS contrainte)
            $table->unsignedBigInteger('periode_paie_id')->nullable();

            $table->date('date_effet');
            $table->date('date_fin')->nullable();

            // Statut
            $table->enum('statut', ['en_attente', 'approuve', 'rejete', 'termine'])->default('en_attente');

            // Motif
            $table->string('motif', 255)->nullable();
            $table->text('observations')->nullable();

            // Numéro d'arrêté
            $table->string('numero_arrete', 50)->nullable();
            $table->date('date_arrete')->nullable();

            // Traçabilité
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('validated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('validated_at')->nullable();

            $table->timestamps();

            $table->index('enseignant_id');
            $table->index('type');
            $table->index('statut');
            $table->index('date_effet');
            $table->index('periode_paie_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mise_a_jour_pc_vac');
    }
};