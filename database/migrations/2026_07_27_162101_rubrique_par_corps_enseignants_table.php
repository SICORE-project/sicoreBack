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
            $table->foreignId('corps_id')->constrained('corps')->onDelete('cascade');
            $table->foreignId('rubrique_paie_id')->constrained('rubrique_paies')->onDelete('cascade');

            // Valeurs personnalisées par corps
            $table->decimal('montant', 15, 2)->nullable();
            $table->boolean('est_applicable')->default(true);
            $table->string('formule_personnalisee', 100)->nullable();
            $table->timestamps();
            
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rubrique_par_corps');
    }
};