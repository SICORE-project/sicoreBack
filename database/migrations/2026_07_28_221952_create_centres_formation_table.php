<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('centres_formation', function (Blueprint $table) {
            $table->id();

            // === IDENTIFICATION ===
            $table->string('code', 20)->unique();
            $table->string('nom', 100);
            $table->string('sigle', 20)->nullable();

            // === LOCALISATION ===
            $table->string('adresse', 255)->nullable();
            $table->string('telephone', 20)->nullable();
            $table->string('email', 100)->nullable();
            $table->string('site_web', 100)->nullable();

            // === HIÉRARCHIE ADMINISTRATIVE ===
            $table->foreignId('ia_id')->nullable()->constrained('ias')->onDelete('set null');
            $table->foreignId('ief_id')->nullable()->constrained('iefs')->onDelete('set null');
            $table->foreignId('region_id')->nullable()->constrained('regions')->onDelete('set null');
            $table->foreignId('departement_id')->nullable()->constrained('departements')->onDelete('set null');
            $table->foreignId('type_etablissement_id')->nullable()->constrained('types_etablissement')->onDelete('set null');

            // === INFORMATIONS SPÉCIFIQUES ===
            $table->enum('statut', ['public', 'prive', 'paritaire'])->default('public');
            $table->string('categorie', 50)->nullable(); // CFPA, Lycée technique, etc.
            $table->integer('capacite_accueil')->nullable();
            $table->integer('nombre_salles')->nullable();
            $table->integer('nombre_ateliers')->nullable();
            $table->boolean('possede_internat')->default(false);
            $table->integer('capacite_internat')->nullable();
            $table->boolean('possede_restaurant')->default(false);

            // === RESPONSABLE ===
            $table->string('directeur_nom', 100)->nullable();
            $table->string('directeur_titre', 50)->nullable();
            $table->string('directeur_telephone', 20)->nullable();
            $table->string('directeur_email', 100)->nullable();

            // === TRACABILITÉ ===
            $table->boolean('est_actif')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');

            $table->timestamps();
            $table->softDeletes();

            // === INDEX ===
            $table->index('code');
            $table->index('nom');
            $table->index('ia_id');
            $table->index('ief_id');
            $table->index('region_id');
            $table->index('departement_id');
            $table->index('statut');
            $table->index('est_actif');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('centres_formation');
    }
};