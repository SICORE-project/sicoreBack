<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Colonnes déduites de App\Models\ServiceFaitHistorique.
 * Journal d'audit des modifications d'un service fait (piste de traçabilité,
 * cf. StoreServiceFaitRequest écrit dans historiques() à chaque action).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('services_faits_historiques')) {
            return;
        }

        Schema::create('services_faits_historiques', function (Blueprint $table) {
            $table->id();

            $table->foreignId('service_fait_id')->constrained('services_faits')->cascadeOnDelete();
            $table->foreignId('utilisateur_id')->nullable()->constrained('users')->nullOnDelete();

            $table->string('action', 100);
            $table->json('anciennes_valeurs')->nullable();
            $table->json('nouvelles_valeurs')->nullable();

            $table->timestamps();

            $table->index('service_fait_id');
            $table->index('action');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('services_faits_historiques');
    }
};
