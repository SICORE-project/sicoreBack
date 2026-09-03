<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('etats_presences', function (Blueprint $table) {
            $table->id();
            $table->integer('nombre_jour');

            $table->foreignId('enseignant_id')
               ->constrained('enseignants')
                ->cascadeOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('etats_presences');
    }
};
