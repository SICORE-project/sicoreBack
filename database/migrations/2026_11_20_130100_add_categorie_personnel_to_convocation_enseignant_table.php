<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Categorie de personnel d'un membre du jury (fonctionnaire / contractuel /
 * vacataire) : declaree dans Convocations::enseignants()->withPivot() et
 * envoyee par le formulaire, mais jamais creee en base -> l'enregistrement
 * des membres echouait. Meme type que la colonne equivalente de
 * convocation_centres (chef de centre / president du jury).
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
        if (Schema::hasColumn('convocation_enseignant', 'categorie_personnel')) {
            Schema::table('convocation_enseignant', function (Blueprint $table) {
                $table->dropColumn('categorie_personnel');
            });
        }
    }
};
