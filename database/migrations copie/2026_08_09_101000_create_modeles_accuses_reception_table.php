<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Colonnes déduites de App\Models\ModeleAccuseReception.
 * Doit être migrée avant accuses_reception (FK modele_id).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('modeles_accuses_reception')) {
            return;
        }

        Schema::create('modeles_accuses_reception', function (Blueprint $table) {
            $table->id();

            $table->string('nom', 150);
            $table->string('objet', 255)->nullable();
            $table->text('contenu')->nullable();
            $table->boolean('actif')->default(true);

            $table->foreignId('cree_par')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->index('actif');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('modeles_accuses_reception');
    }
};
