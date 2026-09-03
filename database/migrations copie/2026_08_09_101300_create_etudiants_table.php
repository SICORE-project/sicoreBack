<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Colonnes déduites de App\Models\Etudiant.
 * Doit être migrée avant attributions_bourses et demandes_aides (FK etudiant_id).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('etudiants')) {
            return;
        }

        Schema::create('etudiants', function (Blueprint $table) {
            $table->id();

            $table->string('matricule', 50)->unique();
            $table->string('nom', 100);
            $table->string('prenom', 100);
            $table->string('filiere', 150)->nullable();
            $table->string('niveau', 50)->nullable();

            $table->foreignId('utilisateur_id')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->index('matricule');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('etudiants');
    }
};
