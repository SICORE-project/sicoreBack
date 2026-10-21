<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('delegation_credits', function (Blueprint $table) {
            $table->id();
            $table->string('annee_academique');
            $table->string('reference_lettre');
            $table->date('date_enregistrement');
            $table->decimal('montant_alouer', 12, 2);

            $table->foreignId('enseignant_id')
                ->constrained('enseignants')
                ->cascadeOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delegation_credits');
    }
};
