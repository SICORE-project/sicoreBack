<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // ✅ Ajouter la colonne deleted_at à la table ias
        Schema::table('ias', function (Blueprint $table) {
            if (!Schema::hasColumn('ias', 'deleted_at')) {
                $table->softDeletes();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // ✅ Supprimer la colonne deleted_at
        Schema::table('ias', function (Blueprint $table) {
            if (Schema::hasColumn('ias', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
        });
    }
};