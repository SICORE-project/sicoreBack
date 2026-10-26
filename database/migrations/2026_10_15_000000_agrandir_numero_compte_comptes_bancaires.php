<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * RIB complet de 24 caractères : code banque (5) + code guichet (5) + numéro de compte (12) + clé (2).
 * Le numéro de compte passe de 11 à 12 caractères.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('comptes_bancaires_enseignants') && Schema::hasColumn('comptes_bancaires_enseignants', 'numero_compte')) {
            Schema::table('comptes_bancaires_enseignants', function (Blueprint $table) {
                $table->string('numero_compte', 12)->nullable()->change();
            });
        }
    }

    public function down(): void
    {
        // Pas de retour en arrière : réduire la colonne tronquerait des numéros de compte.
    }
};
