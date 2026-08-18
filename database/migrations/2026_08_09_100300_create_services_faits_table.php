<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Colonnes déduites de App\Models\ServiceFait et
 * StoreServiceFaitRequest / RejeterServiceFaitRequest / CorrigerServiceFaitRequest.
 * Statuts observés dans ServicesFaitsController : en_attente, valide, rejete.
 * Doit être migrée avant services_faits_historiques (FK service_fait_id).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('services_faits')) {
            return;
        }

        Schema::create('services_faits', function (Blueprint $table) {
            $table->id();

            $table->foreignId('convocation_id')->nullable()->constrained('convocations')->nullOnDelete();
            $table->foreignId('enseignant_id')->constrained('enseignants')->restrictOnDelete();
            $table->foreignId('utilisateur_id')->constrained('users')->restrictOnDelete();

            $table->date('date_debut');
            $table->date('date_fin');
            $table->string('lieu', 255)->nullable();
            $table->text('description')->nullable();
            $table->unsignedInteger('nombre_jours')->nullable();

            $table->enum('statut', ['en_attente', 'valide', 'rejete'])->default('en_attente');
            $table->text('motif_rejet')->nullable();

            $table->foreignId('valide_par')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('valide_at')->nullable();

            $table->timestamps();

            $table->index('statut');
            $table->index('enseignant_id');
            $table->index(['date_debut', 'date_fin']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('services_faits');
    }
};
