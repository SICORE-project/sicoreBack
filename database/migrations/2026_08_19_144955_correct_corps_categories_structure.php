<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Corriger la table corps_enseignant
        Schema::table('corps_enseignant', function (Blueprint $table) {

            if (Schema::hasColumn('corps_enseignant', 'categorie_id')) {

                // Si une clé étrangère existe, on essaie de la supprimer
                try {
                    $table->dropForeign(['categorie_id']);
                } catch (\Throwable $e) {
                    // Rien à faire si aucune contrainte FK n'existe
                }

                $table->dropColumn('categorie_id');
            }
        });

        // Corriger la table categories
        Schema::table('categories', function (Blueprint $table) {

            if (!Schema::hasColumn('categories', 'corps_id')) {
                $table->foreignId('corps_id')
                    ->nullable()
                    ->after('description')
                    ->constrained('corps_enseignant')
                    ->restrictOnDelete();
            }

            if (!Schema::hasColumn('categories', 'est_actif')) {
                $table->boolean('est_actif')
                    ->default(true)
                    ->after('corps_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {

            if (Schema::hasColumn('categories', 'corps_id')) {
                $table->dropForeign(['corps_id']);
                $table->dropColumn('corps_id');
            }

            if (Schema::hasColumn('categories', 'est_actif')) {
                $table->dropColumn('est_actif');
            }
        });

        Schema::table('corps_enseignant', function (Blueprint $table) {

            if (!Schema::hasColumn('corps_enseignant', 'categorie_id')) {
                $table->unsignedBigInteger('categorie_id')
                    ->nullable()
                    ->after('libelle');
            }
        });
    }
};
