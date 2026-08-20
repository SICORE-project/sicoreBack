<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Colonnes déduites de App\Models\piece_justificatives et
 * StorePieceJustificativeRequest / ClassifierPieceJustificativeRequest /
 * VerifierPieceJustificativeRequest / RejeterPieceJustificativeRequest.
 * Statuts observés dans PieceJustificativesController : depose, valide, rejete.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('piece_justificatives')) {
            return;
        }

        Schema::create('piece_justificatives', function (Blueprint $table) {
            $table->id();

            $table->string('type', 100);
            $table->string('document_url')->nullable();
            $table->date('date_depot')->nullable();
            $table->enum('statut', ['depose', 'valide', 'rejete'])->default('depose');
            $table->boolean('dossier_complet')->nullable()->default(false);

            $table->foreignId('convocation_id')->nullable()->constrained('convocations')->nullOnDelete();

            // Métadonnées du fichier uploadé
            $table->string('nom_original')->nullable();
            $table->string('chemin')->nullable();
            $table->string('mime_type', 100)->nullable();
            $table->unsignedBigInteger('taille')->nullable();

            $table->foreignId('depositaire_id')->nullable()->constrained('users')->nullOnDelete();

            // Vérification
            $table->foreignId('verifie_par')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verifie_at')->nullable();
            $table->boolean('conforme')->nullable();
            $table->text('commentaire_verification')->nullable();

            // Validation / rejet
            $table->foreignId('valide_par')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('valide_at')->nullable();
            $table->text('commentaire_rejet')->nullable();

            // Notification
            $table->timestamp('notification_at')->nullable();
            $table->text('notification_message')->nullable();

            $table->timestamps();

            $table->index('statut');
            $table->index('type');
            $table->index('convocation_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('piece_justificatives');
    }
};
