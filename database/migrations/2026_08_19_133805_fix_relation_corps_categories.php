<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->foreignId('corps_id')
                ->nullable()
                ->after('description')
                ->constrained('corps_enseignant')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
        });

        Schema::table('corps_enseignant', function (Blueprint $table) {
            $table->dropColumn('categorie_id');
        });
    }

    public function down(): void
    {
        Schema::table('corps_enseignant', function (Blueprint $table) {
            $table->unsignedBigInteger('categorie_id')
                ->nullable()
                ->after('libelle');
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->dropForeign(['corps_id']);
            $table->dropColumn('corps_id');
        });
    }
};