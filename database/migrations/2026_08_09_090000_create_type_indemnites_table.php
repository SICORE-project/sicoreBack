<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Table des types d'indemnités / barèmes.
 *
 * Colonnes déduites de App\Models\type_indemnites ($fillable, $casts)
 * et des règles de StoreTypeIndemniteRequest / UpdateTypeIndemniteRequest.
 * Doit être migrée AVANT `indemnites` (FK type_indemnite_id).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('type_indemnites')) {
            return;
        }

        Schema::create('type_indemnites', function (Blueprint $table) {
            $table->id();

            $table->string('libelle', 150)->unique();
            $table->text('description')->nullable();

            // Mode de calcul : détermine quel(s) taux s'applique(nt)
            $table->enum('mode_calcul', ['forfaitaire', 'horaire', 'kilometrique']);

            $table->decimal('montant_forfaitaire', 12, 2)->nullable();
            $table->decimal('taux_horaire', 12, 2)->nullable();
            $table->decimal('taux_kilometrique', 12, 2)->nullable();
            $table->decimal('plafond', 12, 2)->nullable();

            $table->boolean('actif')->default(true);

            $table->timestamps();

            $table->index('mode_calcul');
            $table->index('actif');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('type_indemnites');
    }
};
