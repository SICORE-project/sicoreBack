<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Table de paramétrage des types de convocation.
 *
 * Pourquoi une table plutôt qu'un enum ?
 * Le reste de l'application utilise systématiquement des tables de
 * paramétrage pour ce genre de valeur métier (corps_enseignant, grades,
 * statuts_enseignant...), ce qui permet d'ajouter un type sans migration
 * et d'y accrocher plus tard d'autres attributs (ex: barème par défaut,
 * modèle de PDF associé) sans nouvelle colonne sur `convocations`.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('types_convocation')) {
            return;
        }

        Schema::create('types_convocation', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique(); // jury_examen, formation, mission, reunion
            $table->string('libelle', 100);
            $table->text('description')->nullable();
            $table->boolean('est_actif')->default(true);
            $table->timestamps();

            $table->index('code');
            $table->index('est_actif');
        });

        // Seed des types identifiés dans le besoin métier.
        // "jury_examen" est mis en premier et sert de valeur par défaut
        // pour les convocations existantes (cf. migration suivante).
        DB::table('types_convocation')->insert([
            [
                'code' => 'jury_examen',
                'libelle' => "Jury d'examen / certification",
                'description' => "Convocation de membres de jury pour un examen ou une certification (BT, BFEM, CAP...), avec tableau centre/fonction/provenance.",
                'est_actif' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'formation',
                'libelle' => 'Formation / séminaire',
                'description' => 'Convocation à une session de formation ou un séminaire.',
                'est_actif' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'mission',
                'libelle' => 'Mission (ordre de mission)',
                'description' => 'Convocation avec ordre de mission et lieu d\'affectation.',
                'est_actif' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'reunion',
                'libelle' => 'Réunion de service',
                'description' => 'Convocation à une réunion de service.',
                'est_actif' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('types_convocation');
    }
};
