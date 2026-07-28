<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lieux_paiement', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('libelle', 100);
            $table->string('adresse', 255)->nullable();
            $table->string('telephone', 20)->nullable();
            $table->string('email', 100)->nullable();
            $table->foreignId('institut_financier_id')->nullable()->constrained('instituts_financieres')->onDelete('set null');
            $table->boolean('est_actif')->default(true);
            $table->timestamps();

            $table->index('code');
            $table->index('est_actif');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lieux_paiement');
    }
};