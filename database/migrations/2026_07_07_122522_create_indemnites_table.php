<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('indemnites', function (Blueprint $table) {
            $table->id();
            $table->decimal('montant', 12, 2);
            $table->integer('nombre_copies')->nullable();
            $table->boolean('ordre_de_mission')->default(false);
            $table->string('lieu_affectation')->nullable();
            $table->integer('indice')->nullable();
            $table->integer('nombre_heures')->nullable();
            $table->integer('nombre_kilometrages')->nullable();

            $table->foreignId('utilisateur_id')
                ->constrained('utilisateurs')
                ->cascadeOnDelete();

            $table->foreignId('type_indemnite_id')
                ->constrained('type_indemnites')
                ->cascadeOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('indemnites');
    }
};
