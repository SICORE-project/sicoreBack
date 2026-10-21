<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('periode_de_paies', 'id')) {
            Schema::table('periode_de_paies', function (Blueprint $table): void {
                $table->id()->first();
            });
        }

        if (Schema::hasColumn('periode_de_paies', 'annee_academique_id')) {
            Schema::table('periode_de_paies', function (Blueprint $table): void {
                $table->dropConstrainedForeignId('annee_academique_id');
            });
        }

        Schema::table('periode_de_paies', function (Blueprint $table): void {
            $columns = array_values(array_filter([
                'mois',
                'annee',
                'date_debut',
                'date_fin',
                'date_paiement',
                'date_limite_saisie',
                'date_limite_validation',
                'est_fermee',
                'est_active',
                'est_verrouillee',
                'observations',
            ], fn (string $column): bool => Schema::hasColumn('periode_de_paies', $column)));

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }

    public function down(): void
    {
        // Les anciennes colonnes sont volontairement non restaurées : leur
        // suppression peut avoir détruit des données qui ne sont plus fiables.
    }
};
