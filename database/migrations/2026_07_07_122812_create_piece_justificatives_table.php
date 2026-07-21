<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('piece_justificatives', function (Blueprint $table) {
            $table->id();
            $table->string('type')->comment('TypePiece');
            $table->string('document_url');
            $table->date('date_depot');
            $table->enum('statut', ['en_attente', 'valide', 'rejete'])->default('en_attente');
            $table->boolean('dossier_complet')->default(false);

            $table->foreignId('convocation_id')
                ->constrained('convocations')
                ->cascadeOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('piece_justificatives');
    }
};
