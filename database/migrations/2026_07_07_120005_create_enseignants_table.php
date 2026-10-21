<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('enseignants', function (Blueprint $table) {
            $table->id();

            // === IDENTITÉ ===
            $table->string('matricule', 30)->unique();
            $table->string('nom', 50);
            $table->string('prenom', 50);
            $table->date('date_naissance')->nullable();
            $table->string('lieu_naissance', 100)->nullable();
            $table->string('cni', 50)->nullable();
            $table->string('genre', 10)->nullable();
            $table->string('telephone', 20)->nullable();
            $table->string('email', 100)->nullable();
            $table->string('adresse', 255)->nullable();
            $table->string('photo', 255)->nullable();
            $table->decimal('salaire_brut', 15, 2)->nullable();

            // === SITUATION FAMILIALE ===
            $table->unsignedBigInteger('situation_familiale_id')->nullable();
            $table->integer('nombre_enfants')->default(0);
            $table->integer('nombre_femmes')->default(0);
            $table->integer('nombre_parts_fiscales')->nullable();
            $table->boolean('conjoint_travaille')->default(false);

            // === CLÉS ÉTRANGÈRES (SANS contraintes) ===
            $table->unsignedBigInteger('corps_id')->nullable();
            $table->unsignedBigInteger('grade_id')->nullable();
            $table->unsignedBigInteger('echelon_id')->nullable();
            $table->unsignedBigInteger('diplome_id')->nullable();
            $table->unsignedBigInteger('discipline_id')->nullable();
            $table->unsignedBigInteger('specialite_id')->nullable();
            $table->unsignedBigInteger('categorie_id')->nullable();
            $table->unsignedBigInteger('lieu_service_id')->nullable();
            $table->unsignedBigInteger('lieu_paiement_id')->nullable();
            $table->unsignedBigInteger('ief_id')->nullable();
            $table->unsignedBigInteger('ia_id')->nullable();
            $table->unsignedBigInteger('nationalite_id')->nullable();
            $table->unsignedBigInteger('statut_enseignant_id')->nullable();

            // === STATUT ===
            $table->enum('statut', [
                'en_activite',
                'retraite',
                'suspension_provisoire',
                'abandon',
                'decede',
                'integre',
                'radie',
                'cessation_paiement'
            ])->default('en_activite');
            $table->date('date_statut')->nullable();
            $table->date('date_recrutement')->nullable();
            $table->date('date_prise_service')->nullable();
            $table->date('date_fin_contrat')->nullable();
            $table->boolean('est_actif')->default(true);

            // === BANCAIRE ===
            $table->string('numero_compte_bancaire', 34)->nullable();
            $table->string('titulaire_compte', 100)->nullable();
            $table->string('iban', 34)->nullable();
            $table->string('bic', 11)->nullable();
            $table->string('code_banque', 5)->nullable();
            $table->string('code_guichet', 5)->nullable();
            $table->string('cle_rib', 2)->nullable();

            // === INFORMATIONS COMPLÉMENTAIRES ===
            $table->year('annee_recrutement')->nullable();
            $table->string('generation', 20)->nullable();
            $table->text('observations')->nullable();

            // === TRACABILITÉ ===
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // === INDEX ===
            $table->index('matricule');
            $table->index('cni');
            $table->index('corps_id');
            $table->index('lieu_service_id');
            $table->index('ief_id');
            $table->index('statut');
            $table->index('est_actif');
            $table->index('situation_familiale_id');
            $table->index('lieu_paiement_id');
            $table->index('grade_id');
            $table->index('echelon_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('enseignants');
    }
};