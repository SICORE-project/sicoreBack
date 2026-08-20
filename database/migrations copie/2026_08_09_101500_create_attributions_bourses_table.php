<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Colonnes déduites de App\Models\AttributionBourse.
 * Statuts observés dans BoursesController : en_attente, valide, rejete, archive.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('attributions_bourses')) {
            return;
        }

        Schema::create('attributions_bourses', function (Blueprint $table) {
            $table->id();

            $table->foreignId('etudiant_id')->constrained('etudiants')->restrictOnDelete();
            $table->foreignId('type_bourse_id')->constrained('types_bourses')->restrictOnDelete();

            $table->date('date_debut')->nullable();
            $table->date('date_fin')->nullable();
            $table->decimal('montant_mensuel', 12, 2)->nullable();

            $table->enum('statut', ['en_attente', 'valide', 'rejete', 'archive'])->default('en_attente');
            $table->text('commentaire')->nullable();

            $table->foreignId('attribue_par')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->index('statut');
            $table->index('etudiant_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attributions_bourses');
    }
};
