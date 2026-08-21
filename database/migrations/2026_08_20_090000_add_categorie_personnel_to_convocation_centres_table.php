<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Même principe que la provenance (migration
 * add_provenance_to_convocation_centres_table) : le chef de centre et le
 * président du jury n'avaient aucun moyen de saisir leur catégorie de
 * personnel (fonctionnaire/contractuel/vacataire) POUR CETTE convocation,
 * contrairement aux membres du jury qui l'ont via
 * convocation_enseignant.categorie_personnel — d'où un "Statut" toujours
 * vide ou basé sur le seul profil permanent (parfois périmé) de
 * l'enseignant pour ces deux rôles.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('convocation_centres', function (Blueprint $table) {
            if (! Schema::hasColumn('convocation_centres', 'chef_centre_categorie_personnel')) {
                $table->enum('chef_centre_categorie_personnel', ['vacataire', 'contractuel', 'fonctionnaire'])
                    ->nullable()
                    ->after('chef_centre_provenance');
            }

            if (! Schema::hasColumn('convocation_centres', 'president_jury_categorie_personnel')) {
                $table->enum('president_jury_categorie_personnel', ['vacataire', 'contractuel', 'fonctionnaire'])
                    ->nullable()
                    ->after('president_jury_provenance');
            }
        });
    }

    public function down(): void
    {
        Schema::table('convocation_centres', function (Blueprint $table) {
            $table->dropColumn(['chef_centre_categorie_personnel', 'president_jury_categorie_personnel']);
        });
    }
};
