<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Chaque membre du jury (convocation_enseignant) est affecte a UN centre
 * d'examen precis de la convocation (convocation_centres). Nullable car
 * une convocation peut encore ne definir aucun centre (retrocompatibilite
 * avec les convocations existantes / les beneficiaires ajoutes hors
 * wizard, sans notion de centre).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('convocation_enseignant', 'centre_id')) {
            Schema::table('convocation_enseignant', function (Blueprint $table) {
                $table->foreignId('centre_id')->nullable()->after('enseignant_id')
                    ->constrained('convocation_centres')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('convocation_enseignant', 'centre_id')) {
            Schema::table('convocation_enseignant', function (Blueprint $table) {
                $table->dropConstrainedForeignId('centre_id');
            });
        }
    }
};