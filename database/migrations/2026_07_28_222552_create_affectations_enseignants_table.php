<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('affectations_enseignants', function (Blueprint $table) {
            $table->id();

            $table->foreignId('enseignant_id')->constrained('enseignants')->onDelete('cascade');

            // Lieux d'affectation
            $table->foreignId('ia_id')->nullable()->constrained('ias')->onDelete('set null');
            $table->foreignId('ief_id')->nullable()->constrained('iefs')->onDelete('set null');
            $table->foreignId('lieu_service_id')->nullable()->constrained('lieu_de_services')->onDelete('set null');
            $table->foreignId('centre_formation_id')->nullable()->constrained('centres_formation')->onDelete('set null');

            // Date d'affectation
            $table->date('date_debut')->nullable();
            $table->date('date_fin')->nullable();

            // Type d'affectation
            $table->enum('type', ['affectation', 'reaffectation', 'detachement', 'mutation'])->default('affectation');

            // Motif
            $table->string('motif', 255)->nullable();
            $table->text('observations')->nullable();

            // Statut de l'affectation
            $table->boolean('est_active')->default(true);

            // Traçabilité
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');

            $table->timestamps();
            $table->softDeletes();

            $table->index('enseignant_id');
            $table->index('ia_id');
            $table->index('ief_id');
            $table->index('lieu_service_id');
            $table->index('centre_formation_id');
            $table->index('est_active');
            $table->index('date_debut');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('affectations_enseignants');
    }
};