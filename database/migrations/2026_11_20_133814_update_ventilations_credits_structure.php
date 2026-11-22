<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['type_beneficiaire', 'beneficiaire_id'] as $column) {
            if (Schema::hasColumn('ventilations_credits', $column)) {
                Schema::table('ventilations_credits', function (Blueprint $table) use ($column) {
                    $table->dropColumn($column);
                });
            }
        }
    }

    public function down(): void
    {
        // Pas de restauration automatique :
        // les anciennes valeurs ne sont plus disponibles.
    }
};