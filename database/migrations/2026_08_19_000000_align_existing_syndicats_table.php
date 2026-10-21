<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('syndicats', 'libelle')
            && Schema::hasColumn('syndicats', 'nom')) {
            Schema::table('syndicats', function (Blueprint $table) {
                $table->renameColumn('nom', 'libelle');
            });

            Schema::table('syndicats', function (Blueprint $table) {
                $table->unique('libelle');
            });
        }

        if (! Schema::hasColumn('syndicats', 'deleted_at')) {
            Schema::table('syndicats', function (Blueprint $table) {
                $table->softDeletes();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('syndicats', 'deleted_at')) {
            Schema::table('syndicats', function (Blueprint $table) {
                $table->dropSoftDeletes();
            });
        }

        if (Schema::hasColumn('syndicats', 'libelle')
            && ! Schema::hasColumn('syndicats', 'nom')) {
            Schema::table('syndicats', function (Blueprint $table) {
                $table->dropUnique(['libelle']);
                $table->renameColumn('libelle', 'nom');
            });
        }
    }
};
