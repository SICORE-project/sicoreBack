<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('etat_salaires', function (Blueprint $table) {
            $table->id();
            $table->string('libelle');
            $table->string('annee_academique');
            $table->string('periode');
            $table->string('signature')->nullable()->comment('chemin de l\'image de signature');

       $table->foreignId('ia_id')
              ->nullable()
              ->constrained('ias')
                ->nullOnDelete();

            $table->foreignId('ief_id')
                ->nullable()
                ->constrained('iefs')
                ->nullOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('etat_salaires');
    }
};
