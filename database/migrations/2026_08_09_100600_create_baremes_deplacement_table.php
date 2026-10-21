<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Colonnes déduites de App\Models\BaremeDeplacement.
 * Doit être migrée avant lignes_frais_deplacement (FK bareme_id).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('baremes_deplacement')) {
            return;
        }

        Schema::create('baremes_deplacement', function (Blueprint $table) {
            $table->id();

            $table->string('libelle', 150);
            $table->string('type_frais', 100); // ex: transport, hebergement, restauration
            $table->string('zone', 100)->nullable();
            $table->string('moyen_transport', 100)->nullable();
            $table->decimal('taux_unitaire', 12, 2);
            $table->decimal('plafond', 12, 2)->nullable();
            $table->boolean('justificatif_obligatoire')->default(false);
            $table->date('date_effet')->nullable();
            $table->date('date_fin')->nullable();
            $table->boolean('actif')->default(true);

            $table->foreignId('cree_par')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->index('type_frais');
            $table->index('actif');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('baremes_deplacement');
    }
};
