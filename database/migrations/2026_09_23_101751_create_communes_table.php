<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('communes', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('libelle', 100);
            $table->foreignId('region_id')->constrained('regions')->restrictOnDelete();
            $table->foreignId('departement_id')->constrained('departements')->restrictOnDelete();
            $table->boolean('est_actif')->default(true);
            $table->timestamps();

            $table->index('libelle');
            $table->index('est_actif');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('communes');
    }
};