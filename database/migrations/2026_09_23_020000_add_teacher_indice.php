<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Vérifier si la colonne existe déjà avant de l'ajouter
        if (!Schema::hasColumn('enseignants', 'indice')) {
            Schema::table('enseignants', function (Blueprint $table) {
                $table->unsignedInteger('indice')->nullable();
            });
        }
    }

    public function down(): void
    {
        // Vérifier si la colonne existe avant de la supprimer
        if (Schema::hasColumn('enseignants', 'indice')) {
            Schema::table('enseignants', function (Blueprint $table) {
                $table->dropColumn('indice');
            });
        }
    }
};