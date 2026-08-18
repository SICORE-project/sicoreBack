<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rattache une piece justificative a UN membre du jury precis (bouton
 * "Ajouter une piece" sur la fiche d'un membre, page Pieces
 * justificatives) et, en denormalisation utile pour le suivi "par centre"
 * de cette page, au centre d'examen de ce membre — jusqu'ici
 * piece_justificatives n'etait rattachee qu'a la convocation entiere, ce
 * qui ne permettait pas de savoir a QUI appartenait un document depose.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('piece_justificatives', 'enseignant_id')) {
            return;
        }

        Schema::table('piece_justificatives', function (Blueprint $table) {
            $table->foreignId('enseignant_id')->nullable()->after('convocation_id')
                ->constrained('enseignants')->nullOnDelete();

            $table->foreignId('centre_id')->nullable()->after('enseignant_id')
                ->constrained('convocation_centres')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('piece_justificatives', function (Blueprint $table) {
            $table->dropConstrainedForeignId('enseignant_id');
            $table->dropConstrainedForeignId('centre_id');
        });
    }
};
