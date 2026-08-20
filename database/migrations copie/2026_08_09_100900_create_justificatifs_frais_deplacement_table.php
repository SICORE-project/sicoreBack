<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Colonnes déduites de App\Models\JustificatifFraisDeplacement et
 * DeposerJustificatifFraisRequest.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('justificatifs_frais_deplacement')) {
            return;
        }

        Schema::create('justificatifs_frais_deplacement', function (Blueprint $table) {
            $table->id();

            $table->foreignId('mission_id')->constrained('missions_deplacement')->cascadeOnDelete();
            $table->foreignId('ligne_frais_id')->nullable()->constrained('lignes_frais_deplacement')->nullOnDelete();

            $table->string('nom_original')->nullable();
            $table->string('chemin');
            $table->string('mime_type', 100)->nullable();
            $table->unsignedBigInteger('taille')->nullable();

            $table->foreignId('depose_par')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('conforme')->nullable();
            $table->foreignId('verifie_par')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verifie_at')->nullable();
            $table->text('commentaire')->nullable();

            $table->timestamps();

            $table->index('mission_id');
            $table->index('ligne_frais_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('justificatifs_frais_deplacement');
    }
};
