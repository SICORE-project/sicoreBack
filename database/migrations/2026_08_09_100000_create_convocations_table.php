<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Colonnes déduites de App\Models\Convocations et StoreConvocationRequest.
 * Doit être migrée avant : convocation_enseignant, convocation_envois,
 * services_faits, piece_justificatives, accuses_reception (toutes ont
 * une FK convocation_id).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('convocations')) {
            return;
        }

        Schema::create('convocations', function (Blueprint $table) {
            $table->id();

            $table->date('date_emission');
            $table->string('objet', 255);
            $table->string('lieu_examen', 255)->nullable();
            $table->boolean('ordre_de_mission')->nullable()->default(false);
            $table->string('lieu_affectation', 255)->nullable();
            $table->enum('statut', ['brouillon', 'emise', 'envoyee', 'cloturee'])->default('brouillon');

            $table->foreignId('utilisateur_id')->constrained('users')->restrictOnDelete();

            $table->timestamps();

            $table->index('statut');
            $table->index('date_emission');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('convocations');
    }
};
