<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rubrique_paies', function (Blueprint $table) {
            // 1. On ajoute la colonne montant après formule_calcul
            $table->decimal('montant', 12, 2)->nullable()->after('formule_calcul');
            
            // 2. On supprime la colonne description
            $table->dropColumn('description');
        });
    }

    public function down(): void
    {
        Schema::table('rubrique_paies', function (Blueprint $table) {
            // En cas de rollback, on remet description et on supprime montant
            $table->string('description')->nullable()->after('formule_calcul');
            $table->dropColumn('montant');
        });
    }
};