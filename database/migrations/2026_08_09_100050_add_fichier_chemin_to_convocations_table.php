<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Colonne manquante : App\Models\Convocations::$fillable et
 * ConvocationFichierController::store() lisent/écrivent 'fichier_chemin',
 * mais la migration initiale (2026_08_09_100000) ne la créait pas.
 * Sans elle, l'upload du fichier de convocation échoue en base.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('convocations', 'fichier_chemin')) {
            Schema::table('convocations', function (Blueprint $table) {
                $table->string('fichier_chemin')->nullable()->after('lieu_affectation');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('convocations', 'fichier_chemin')) {
            Schema::table('convocations', function (Blueprint $table) {
                $table->dropColumn('fichier_chemin');
            });
        }
    }
};
