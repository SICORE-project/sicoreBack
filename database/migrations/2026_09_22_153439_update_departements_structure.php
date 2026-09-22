<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('departements', function (Blueprint $table) {
            $table->renameColumn('nom', 'libelle');
        });

        Schema::table('departements', function (Blueprint $table) {
            $table->dropColumn([
                'chef_lieu',
                'population',
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('departements', function (Blueprint $table) {
            $table->string('chef_lieu', 50)->nullable();
            $table->integer('population')->nullable();
        });

        Schema::table('departements', function (Blueprint $table) {
            $table->renameColumn('libelle', 'nom');
        });
    }
};