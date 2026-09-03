<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('transferts_enseignants')) {
            return;
        }

        Schema::create('transferts_enseignants', function (Blueprint $table) {
            $table->id();

            $table->foreignId('enseignant_id')->constrained('enseignants')->onDelete('cascade');

            // Ancien lieu
            $table->foreignId('ancien_lieu_service_id')->nullable()->constrained('lieu_de_services')->onDelete('set null');
            $table->foreignId('ancien_ief_id')->nullable();
            $table->foreignId('ancien_ia_id')->nullable();
            $table->foreignId('ancien_centre_formation_id')->nullable()->constrained('centres_formation')->onDelete('set null');

            // Nouveau lieu
            $table->foreignId('nouveau_lieu_service_id')->nullable()->constrained('lieu_de_services')->onDelete('set null');
            $table->foreignId('nouveau_ief_id')->nullable();
            $table->foreignId('nouveau_ia_id')->nullable();
            $table->foreignId('nouveau_centre_formation_id')->nullable()->constrained('centres_formation')->onDelete('set null');

            // Dates
            $table->date('date_transfert');
            $table->date('date_prise_effet')->nullable();

            // Statut de validation
            $table->enum('statut', ['en_attente', 'valide', 'rejete', 'annule'])->default('en_attente');

            // Motif
            $table->string('motif', 255)->nullable();
            $table->text('observations')->nullable();

            // Numéro d'arrêté
            $table->string('numero_arrete', 50)->nullable();
            $table->date('date_arrete')->nullable();

            // Validation
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('validated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('validated_at')->nullable();

            $table->timestamps();

            $table->index('enseignant_id');
            $table->index('statut');
            $table->index('date_transfert');
            $table->index('ancien_centre_formation_id');
            $table->index('nouveau_centre_formation_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transferts_enseignants');
    }
};
