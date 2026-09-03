<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * La categorie de personnel (fonctionnaire/contractuel/vacataire) saisie
 * pour un membre du jury — wizard de creation (convocation-wizard.js) et
 * import Word (colonne "Categorie de personnel") — était collectée cote
 * front/service mais n'avait nulle part ou etre persistee : ni colonne sur
 * ce pivot, ni transmission jusqu'au sync(), d'ou un "Statut" toujours vide
 * sur la fiche de la convocation.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('convocation_enseignant', 'categorie_personnel')) {
            return;
        }

        Schema::table('convocation_enseignant', function (Blueprint $table) {
            $table->enum('categorie_personnel', ['vacataire', 'contractuel', 'fonctionnaire'])
                ->nullable()
                ->after('provenance');
        });
    }

    public function down(): void
    {
        Schema::table('convocation_enseignant', function (Blueprint $table) {
            $table->dropColumn('categorie_personnel');
        });
    }
};
