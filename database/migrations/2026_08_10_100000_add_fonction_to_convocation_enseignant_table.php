<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * La convocation (cf. modele papier "convocation jury BT") associe a chaque
 * beneficiaire une fonction propre a CETTE convocation (President de jury,
 * Surveillant/correcteur, ...). Ce n'est pas un attribut fixe de l'enseignant,
 * donc elle doit vivre sur le pivot convocation_enseignant et non sur la
 * table enseignants.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('convocation_enseignant', 'fonction')) {
            Schema::table('convocation_enseignant', function (Blueprint $table) {
                $table->string('fonction', 100)->nullable()->after('enseignant_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('convocation_enseignant', 'fonction')) {
            Schema::table('convocation_enseignant', function (Blueprint $table) {
                $table->dropColumn('fonction');
            });
        }
    }
};
