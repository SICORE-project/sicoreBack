<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rubrique_paies', function (Blueprint $table): void {
            $columns = array_values(array_filter([
                'est_cotisable',
                'est_imposable',
                'est_afficher_bulletin',
                'taux_defaut',
                'montant_defaut',
                'formule_calcul',
                'description',
            ], fn (string $column): bool => Schema::hasColumn('rubrique_paies', $column)));

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }

    public function down(): void
    {
        // Les anciennes colonnes supprimées ne sont volontairement pas restaurées.
    }
};
