<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('regions', function (Blueprint $table) {
            $table->renameColumn('nom', 'libelle');
        });

        Schema::table('regions', function (Blueprint $table) {
            $table->unique('libelle');
        });
    }

    public function down(): void
    {
        Schema::table('regions', function (Blueprint $table) {
            $table->dropUnique(['libelle']);
        });

        Schema::table('regions', function (Blueprint $table) {
            $table->renameColumn('libelle', 'nom');
        });
    }
};