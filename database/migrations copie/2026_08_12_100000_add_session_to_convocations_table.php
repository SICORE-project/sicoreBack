<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Colonne "Session" (ex: "BFEM 2026") demandée par le point 3 du cahier
 * des charges "Transmission des convocations à la DAGE" — c'est une
 * colonne de la liste DAGE, qui n'existait nulle part avant cette
 * migration (ni sur `convocations`, ni ailleurs).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('convocations', 'session')) {
            Schema::table('convocations', function (Blueprint $table) {
                $table->string('session', 150)->nullable()->after('objet');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('convocations', 'session')) {
            Schema::table('convocations', function (Blueprint $table) {
                $table->dropColumn('session');
            });
        }
    }
};
