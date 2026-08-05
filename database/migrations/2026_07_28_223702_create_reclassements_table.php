<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reclassements', function (Blueprint $table) {
            $table->id();

            $table->foreignId('enseignant_id')->constrained('enseignants')->onDelete('cascade');

            // Anciennes valeurs
            $table->unsignedBigInteger('ancien_corps_id')->nullable();
            $table->string('ancien_statut', 30)->nullable();

            // Nouvelles valeurs
            $table->unsignedBigInteger('nouveau_corps_id')->nullable();
            $table->string('nouveau_statut', 30)->nullable();

            // Informations
            $table->date('date_reclassement');
            $table->string('motif', 255)->nullable();
            $table->text('observations')->nullable();

            $table->string('numero_arrete', 50)->nullable();
            $table->date('date_arrete')->nullable();

            // Traçabilité
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('validated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('validated_at')->nullable();

            $table->timestamps();

            $table->index('enseignant_id');
            $table->index('date_reclassement');
            $table->index('ancien_corps_id');
            $table->index('nouveau_corps_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reclassements');
    }
};