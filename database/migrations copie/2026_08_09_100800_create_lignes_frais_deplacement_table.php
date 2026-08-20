<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Colonnes déduites de App\Models\LigneFraisDeplacement et
 * CalculerFraisDeplacementRequest (lignes.*).
 * Doit être migrée avant justificatifs_frais_deplacement (FK ligne_frais_id).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('lignes_frais_deplacement')) {
            return;
        }

        Schema::create('lignes_frais_deplacement', function (Blueprint $table) {
            $table->id();

            $table->foreignId('mission_id')->constrained('missions_deplacement')->cascadeOnDelete();
            $table->foreignId('bareme_id')->nullable()->constrained('baremes_deplacement')->nullOnDelete();

            $table->string('type_frais', 100);
            $table->decimal('quantite', 10, 2);
            $table->decimal('taux_unitaire', 12, 2)->nullable();
            $table->decimal('montant_declare', 12, 2)->nullable();
            $table->decimal('montant_calcule', 12, 2)->nullable();
            $table->decimal('montant_approuve', 12, 2)->nullable();
            $table->decimal('plafond_applique', 12, 2)->nullable();
            $table->boolean('justificatif_obligatoire')->nullable()->default(false);
            $table->string('description', 255)->nullable();

            $table->timestamps();

            $table->index('mission_id');
            $table->index('type_frais');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lignes_frais_deplacement');
    }
};
