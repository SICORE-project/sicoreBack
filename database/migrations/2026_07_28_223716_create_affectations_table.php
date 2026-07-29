<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('affectations', function (Blueprint $table) {
            $table->id();

            $table->foreignId('enseignant_id')->constrained('enseignants')->onDelete('cascade');

            // Ancien lieu
            $table->foreignId('ancien_lieu_service_id')->nullable()->constrained('lieu_de_services')->onDelete('set null');
            $table->foreignId('ancien_ief_id')->nullable()->constrained('iefs')->onDelete('set null');
            $table->foreignId('ancien_ia_id')->nullable()->constrained('ias')->onDelete('set null');
            $table->foreignId('ancien_centre_formation_id')->nullable()->constrained('centres_formation')->onDelete('set null');

            // Nouveau lieu
            $table->foreignId('nouveau_lieu_service_id')->nullable()->constrained('lieu_de_services')->onDelete('set null');
            $table->foreignId('nouveau_ief_id')->nullable()->constrained('iefs')->onDelete('set null');
            $table->foreignId('nouveau_ia_id')->nullable()->constrained('ias')->onDelete('set null');
            $table->foreignId('nouveau_centre_formation_id')->nullable()->constrained('centres_formation')->onDelete('set null');

            // Type d'affectation
            $table->enum('type', ['affectation', 'reaffectation', 'mutation', 'detachement', 'transfert'])->default('affectation');

            // Dates
            $table->date('date_effet');
            $table->date('date_fin')->nullable();

            // Motif
            $table->string('motif', 255)->nullable();
            $table->text('observations')->nullable();

            // Numéro d'arrêté
            $table->string('numero_arrete', 50)->nullable();
            $table->date('date_arrete')->nullable();

            // Statut de l'affectation
            $table->enum('statut', ['en_attente', 'approuve', 'rejete', 'annule', 'termine'])->default('en_attente');

            // Traçabilité
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('validated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('validated_at')->nullable();

            $table->timestamps();

            $table->index('enseignant_id');
            $table->index('type');
            $table->index('date_effet');
            $table->index('statut');
            $table->index('nouveau_lieu_service_id');
            $table->index('nouveau_ief_id');
            $table->index('nouveau_ia_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('affectations');
    }
};