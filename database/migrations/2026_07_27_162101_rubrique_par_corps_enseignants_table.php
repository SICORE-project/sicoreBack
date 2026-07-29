<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rubrique_par_corps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('corps_id')->constrained('corps_enseignant')->onDelete('cascade');  // <-- CORRIGÉ
            $table->foreignId('rubrique_paie_id')->constrained('rubrique_paies')->onDelete('cascade');
            $table->decimal('taux_personnalise', 8, 2)->nullable();
            $table->decimal('montant_personnalise', 15, 2)->nullable();
            $table->boolean('est_applicable')->default(true);
            $table->string('formule_personnalisee', 100)->nullable();
            $table->boolean('est_actif')->default(true);
            $table->timestamps();

            $table->unique(['corps_id', 'rubrique_paie_id']);
            $table->index('corps_id');
            $table->index('rubrique_paie_id');
            $table->index('est_actif');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rubrique_par_corps');
    }
};