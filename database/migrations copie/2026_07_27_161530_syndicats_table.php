<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('syndicats', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('nom', 100);
            $table->string('sigle', 20)->nullable();
            $table->string('adresse', 255)->nullable();
            $table->string('telephone', 20)->nullable();
            $table->string('email', 100)->nullable();
            $table->string('site_web', 100)->nullable();
            $table->string('responsable', 100)->nullable();
            $table->string('responsable_titre', 50)->nullable();
            $table->decimal('taux_cotisation', 8, 2)->default(0);  // <-- PRÉCISION 8, pas 255
            $table->boolean('est_actif')->default(true);
            $table->timestamps();

            $table->index('code');
            $table->index('est_actif');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('syndicats');
    }
};