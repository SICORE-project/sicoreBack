<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('etat_paie_indemnites', function (Blueprint $table) {
            $table->id();
            $table->date('date_generation');
            $table->decimal('total_montant', 12, 2)->default(0);
            $table->string('lieu_examen')->nullable();
            $table->boolean('transmit_sica')->default(false);

            $table->foreignId('utilisateur_id')
                ->nullable()
                ->constrained('utilisateurs')
                ->nullOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('etat_paie_indemnites');
    }
};
