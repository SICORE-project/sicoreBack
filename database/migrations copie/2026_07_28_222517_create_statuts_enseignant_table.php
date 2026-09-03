<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('statuts_enseignant', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('libelle', 50);
            $table->string('categorie', 30)->nullable(); // actif, inactif, temporaire
            $table->text('description')->nullable();
            $table->boolean('est_disponible_liste')->default(true);
            $table->boolean('est_actif')->default(true);
            $table->timestamps();

            $table->index('code');
            $table->index('categorie');
            $table->index('est_actif');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('statuts_enseignant');
    }
};