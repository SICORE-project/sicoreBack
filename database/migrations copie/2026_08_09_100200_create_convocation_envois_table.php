<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Colonnes déduites de App\Models\ConvocationEnvoi et EnvoyerConvocationRequest /
 * RelancerConvocationRequest / de la logique de ConvocationEnvoiController
 * (statut d'envoi individuel par bénéficiaire : envoye | echec, canal email/sms/courrier).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('convocation_envois')) {
            return;
        }

        Schema::create('convocation_envois', function (Blueprint $table) {
            $table->id();

            $table->foreignId('convocation_id')->constrained('convocations')->cascadeOnDelete();
            $table->foreignId('enseignant_id')->constrained('enseignants')->restrictOnDelete();

            $table->enum('canal', ['email', 'sms', 'courrier'])->default('email');
            $table->enum('statut', ['envoye', 'echec'])->default('envoye');
            $table->text('message')->nullable();
            $table->dateTime('date_envoi')->nullable();

            $table->timestamps();

            $table->index('convocation_id');
            $table->index('enseignant_id');
            $table->index('statut');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('convocation_envois');
    }
};
