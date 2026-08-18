<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bultins', function (Blueprint $table) {
            $table->string('reference')->unique()->nullable()->after('numero_ordre');
            $table->enum('statut', ['genere', 'valide', 'paye', 'annule'])->default('genere')->after('reference');
            $table->decimal('salaire_brut', 12, 2)->default(0)->after('statut');
            $table->decimal('total_retenues', 12, 2)->default(0)->after('salaire_brut');
        });

        Schema::table('rubrique_bultins', function (Blueprint $table) {
            $table->enum('type', ['gain', 'retenue'])->default('gain')->after('libelle');
        });
    }

    public function down(): void
    {
        Schema::table('bultins', function (Blueprint $table) {
            $table->dropColumn(['reference', 'statut', 'salaire_brut', 'total_retenues']);
        });

        Schema::table('rubrique_bultins', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
};
