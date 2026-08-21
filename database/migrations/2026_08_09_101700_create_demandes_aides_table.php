<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Colonnes déduites de App\Models\DemandeAide.
 * Statuts observés dans AidesEtudiantesController : en_attente, valide, rejete, archive.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('demandes_aides')) {
            return;
        }

        Schema::create('demandes_aides', function (Blueprint $table) {
            $table->id();

            $table->string('reference')->unique();
            $table->foreignId('type_aide_id')->constrained('types_aides')->restrictOnDelete();
            $table->foreignId('etudiant_id')->constrained('etudiants')->restrictOnDelete();
            $table->foreignId('utilisateur_id')->nullable()->constrained('users')->nullOnDelete();

            $table->text('motif')->nullable();
            $table->enum('statut', ['en_attente', 'valide', 'rejete', 'archive'])->default('en_attente');
            $table->decimal('montant_attribue', 12, 2)->nullable();
            $table->text('commentaire_etude')->nullable();
            $table->text('motif_rejet')->nullable();

            $table->foreignId('traite_par')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('traite_at')->nullable();
            $table->timestamp('notification_at')->nullable();

            $table->timestamps();

            $table->index('statut');
            $table->index('etudiant_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('demandes_aides');
    }
};
