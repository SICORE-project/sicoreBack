<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mise_a_jour_mc_ve', function (Blueprint $table) {
            $table->id();

            $table->foreignId('enseignant_id')->constrained('enseignants')->onDelete('cascade');

            // Type de mise à jour
            $table->enum('type', ['mc', 've', 'double_flux'])->default('mc');

            // Informations
            $table->string('libelle', 100);
            $table->decimal('montant', 15, 2)->nullable();
            $table->date('date_effet');
            $table->date('date_fin')->nullable();

            // Période
            $table->foreignId('periode_paie_id')->nullable()->constrained('periode_de_paies')->onDelete('set null');

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
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mise_a_jour_mc_ve');
    }
};