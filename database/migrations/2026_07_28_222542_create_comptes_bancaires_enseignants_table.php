<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comptes_bancaires_enseignants', function (Blueprint $table) {
            $table->id();

            $table->foreignId('enseignant_id')->constrained('enseignants')->onDelete('cascade');

            // Informations bancaires (comme dans votre capture)
            $table->string('code_banque', 5)->nullable();      // Lg 5 c
            $table->string('code_guichet', 5)->nullable();     // Lg 5 c
            $table->string('numero_compte', 11)->nullable();   // Lg 5 c (en réalité 11 caractères)
            $table->string('cle_rib', 2)->nullable();          // Clé RIB (2 caractères)
            $table->string('iban', 34)->nullable();
            $table->string('bic', 11)->nullable();
            $table->string('titulaire_compte', 100)->nullable();

            // Type de virement
            $table->enum('type_virement', ['unitaire', 'masse'])->default('unitaire');

            // Institution financière
            $table->foreignId('institut_financier_id')->nullable()->constrained('instituts_financieres')->onDelete('set null');

            $table->boolean('est_principal')->default(false);
            $table->boolean('est_actif')->default(true);
            $table->timestamps();

            $table->index('enseignant_id');
            $table->index('est_principal');
            $table->index('est_actif');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comptes_bancaires_enseignants');
    }
};