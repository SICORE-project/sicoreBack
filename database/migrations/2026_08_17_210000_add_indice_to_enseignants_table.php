<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * "Fiche de déplacement" (module Frais de déplacement) : pour un
 * bénéficiaire fonctionnaire, le formulaire doit récupérer son indice au
 * lieu de le faire ressaisir à chaque fois — cette colonne n'existait pas
 * du tout sur `enseignants` (vérifié : absente de la migration de création
 * de la table et de tout le reste du schéma). Ajoutée ici avec l'accord
 * explicite de l'utilisatrice (la table `enseignants` appartient au module
 * Paramétrage, pas à Indemnités).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('enseignants', 'indice')) {
            return;
        }

        Schema::table('enseignants', function (Blueprint $table) {
            $table->decimal('indice', 10, 2)->nullable()->after('categorie_personnel');
        });
    }

    public function down(): void
    {
        Schema::table('enseignants', function (Blueprint $table) {
            $table->dropColumn('indice');
        });
    }
};
