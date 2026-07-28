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
            $table->string('cni', 50)->unique();
            $table->decimal('salaire brut', 15, 2)->nullable();
            $table->string('lieu de paiment')->nullable();
            $table->enum('sexe', ['M', 'F'])->nullable();
            $table->string('telephone', 20)->nullable();
            $table->string('email', 100)->nullable();
            $table->string('adresse', 255)->nullable();
            $table->string('photo', 255)->nullable();

            // === SITUATION FAMILIALE ===
            $table->string('situation_matrimoniale', 30)->nullable();
            $table->integer('nombre_enfants')->default(0);
            $table->integer('nombre_de_femmes')->nullable();
            $table->integer('nombre_de_parts')->nullable();
            $table->boolean('si_conjoint_travail')->default(false);

            // === CLÉS ÉTRANGÈRES ===
            $table->foreignId('corps_id')->constrained('corps_enseignant')->onDelete('cascade');
          //  $table->foreignId('grade_id')->nullable()->constrained('grades')->onDelete('set null');
           // $table->foreignId('echelon_id')->nullable()->constrained('echelons')->onDelete('set null');
            $table->foreignId('diplome_id')->nullable()->constrained('diplomes')->onDelete('set null');
            $table->foreignId('discipline_id')->nullable()->constrained('disciplines')->onDelete('set null');
            $table->foreignId('syndicat_id')->nullable()->contrained('syndicats')->onDelete('set null');
            $table->foreignId('specialite_id')->nullable()->constrained('specialites')->onDelete('set null');
            $table->foreignId('categorie_id')->nullable()->contrained('categories')->onDelete('set null');
            $table->foreignId('lieu_service_id')->constrained('lieu_de_services')->onDelete('cascade');
            $table->foreignId('ief_id')->constrained('iefs')->onDelete('cascade');
            $table->foreignId('ia_id')->nullable()->constrained('ias')->onDelete('set null');
            $table->foreignId('nationalite_id')->nullable()->constrained('nationalites')->onDelete('set null');

            // === STATUT ===
            $table->enum('statut', [ 'en activite', 'retraite', 'suspension provisoire', 'abandon', 'decede', 'integre', 'radie', 'cessation paiement', ]);
            $table->date('date_statut')->nullable();
            $table->date('date_recrutement')->nullable();
            $table->date('date_prise_service')->nullable();
            $table->boolean('est_actif')->default(true);

            // === BANCAIRE ===
            //$table->string('iban', 34)->nullable();
            $table->string('Num_compte_bancaire')->nullable();
            $table->string('titulaire_compte', 100)->nullable();

            // === TRACABILITÉ ===
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');

            $table->timestamps();
            $table->softDeletes();

            // === INDEX ===
            $table->index('matricule');
            $table->index('corps_id');
            $table->index('lieu_service_id');
            $table->index('ief_id');
            $table->index('statut');
            $table->index('est_actif');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('enseignants');
    }
};
