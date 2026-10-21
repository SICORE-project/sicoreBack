<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cessations_paiement', function (Blueprint $table) {
            $table->id();

            $table->foreignId('enseignant_id')->constrained('enseignants')->onDelete('cascade');

            $table->enum('type', [
                'cessation_paiement',
                'abandon',
                'radie',
                'retraite',
                'decede',
                'integre',
                'suspension_provisoire'
            ])->default('cessation_paiement');

            $table->date('date_effet');
            $table->date('date_reprise')->nullable();
            $table->string('motif', 255)->nullable();
            $table->text('observations')->nullable();

            $table->boolean('est_definitif')->default(false);
            $table->boolean('est_actif')->default(true);

            // Numéro d'arrêté
            $table->string('numero_arrete', 50)->nullable();
            $table->date('date_arrete')->nullable();

            // Traçabilité
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('validated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('validated_at')->nullable();

            $table->timestamps();

            $table->index('enseignant_id');
            $table->index('type');
            $table->index('date_effet');
            $table->index('est_actif');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cessations_paiement');
    }
};