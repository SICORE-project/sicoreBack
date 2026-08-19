<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('comptes_bancaires_enseignants', function (Blueprint $table) {
            $table->string('numero_compte', 34)->nullable()->change();
            $table->string('rib', 34)->nullable()->after('numero_compte');
        });
    }

    public function down(): void
    {
        Schema::table('comptes_bancaires_enseignants', function (Blueprint $table) {
            $table->dropColumn('rib');
            $table->string('numero_compte', 11)->nullable()->change();
        });
    }
};
