<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Table pivot utilisée par Convocations::enseignants() (belongsToMany),
 * pour la gestion des bénéficiaires d'une convocation.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('convocation_enseignant')) {
            return;
        }

        Schema::create('convocation_enseignant', function (Blueprint $table) {
            $table->id();

            $table->foreignId('convocation_id')->constrained('convocations')->cascadeOnDelete();
            $table->foreignId('enseignant_id')->constrained('enseignants')->cascadeOnDelete();

            $table->timestamps();

            $table->unique(['convocation_id', 'enseignant_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('convocation_enseignant');
    }
};
