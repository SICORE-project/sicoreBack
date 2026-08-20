<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Table des états de paie des indemnités (individuels ou consolidés).
 *
 * Colonnes déduites de App\Models\etat_paie_indemnites ($fillable, $casts)
 * et des règles de StoreEtatPaieIndemniteRequest / UpdateEtatPaieIndemniteRequest /
 * GenererEtatPaieIndemniteRequest, ainsi que du workflow de statuts observé dans
 * EtatPaieIndemnitesController (brouillon -> genere -> valide -> archive / transmis).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('etat_paie_indemnites')) {
            return;
        }

        Schema::create('etat_paie_indemnites', function (Blueprint $table) {
            $table->id();

            $table->string('reference')->unique(); // ex: EP-XXXXXXXX, généré dans le contrôleur
            $table->string('type', 100); // individuel | consolide

            $table->foreignId('beneficiaire_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('utilisateur_id')->nullable()->constrained('users')->nullOnDelete(); // générateur (auth user)

            $table->date('date_generation')->nullable();
            $table->date('periode_debut');
            $table->date('periode_fin');

            $table->string('lieu_examen', 255)->nullable();
            $table->string('session', 100)->nullable();

            $table->json('perimetre')->nullable();
            $table->json('details')->nullable();
            $table->decimal('total_montant', 14, 2)->nullable()->default(0);

            $table->boolean('transmit_sica')->default(false);
            $table->enum('statut', ['brouillon', 'genere', 'valide', 'archive', 'transmis'])->default('brouillon');
            $table->boolean('verrouille')->default(false);

            $table->foreignId('valide_par')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('valide_at')->nullable();
            $table->text('commentaire_correction')->nullable();

            $table->foreignId('archive_par')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('archive_at')->nullable();

            $table->timestamps();

            $table->index('statut');
            $table->index('type');
            $table->index('beneficiaire_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('etat_paie_indemnites');
    }
};
