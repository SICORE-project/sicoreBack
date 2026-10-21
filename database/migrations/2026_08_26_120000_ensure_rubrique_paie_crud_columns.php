<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('rubrique_paies', 'periodicite')) {
            Schema::table('rubrique_paies', function (Blueprint $table): void {
                $table->string('periodicite', 20)->default('mensuelle')->index();
            });
        }

        if (! Schema::hasColumn('rubrique_paies', 'description')) {
            Schema::table('rubrique_paies', function (Blueprint $table): void {
                $table->text('description')->nullable();
            });
        }

        if (! Schema::hasColumn('rubrique_paies', 'created_at')) {
            Schema::table('rubrique_paies', function (Blueprint $table): void {
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        // Cette migration répare un schéma existant et ne supprime aucune donnée.
    }
};
