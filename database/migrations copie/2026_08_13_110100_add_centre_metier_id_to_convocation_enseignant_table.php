<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Chaque membre du jury peut desormais etre rattache a UN metier precis du
 * centre (convocation_centre_metiers), pas seulement au centre dans son
 * ensemble (convocation_enseignant.centre_id, qui reste en base pour la
 * retrocompatibilite mais n'est plus le rattachement principal).
 *
 * Migration des donnees existantes : chaque centre qui avait deja un
 * "metier" (ancien champ texte unique sur convocation_centres) devient son
 * premier metier dans convocation_centre_metiers, et les beneficiaires deja
 * rattaches a ce centre (via centre_id) sont rattaches a ce metier — sans
 * cela, ils "disparaitraient" des nouvelles vues groupees par metier.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('convocation_enseignant', 'centre_metier_id')) {
            Schema::table('convocation_enseignant', function (Blueprint $table) {
                $table->foreignId('centre_metier_id')->nullable()->after('centre_id')
                    ->constrained('convocation_centre_metiers')->nullOnDelete();
            });
        }

        $centres = DB::table('convocation_centres')
            ->whereNotNull('metier')
            ->where('metier', '!=', '')
            ->get(['id', 'metier']);

        foreach ($centres as $centre) {
            // Deja migre (relancer la migration ne doit pas dupliquer le metier).
            $metierExistant = DB::table('convocation_centre_metiers')
                ->where('convocation_centre_id', $centre->id)
                ->where('metier', $centre->metier)
                ->first();

            $metierId = $metierExistant->id ?? DB::table('convocation_centre_metiers')->insertGetId([
                'convocation_centre_id' => $centre->id,
                'metier' => $centre->metier,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('convocation_enseignant')
                ->where('centre_id', $centre->id)
                ->whereNull('centre_metier_id')
                ->update(['centre_metier_id' => $metierId]);
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('convocation_enseignant', 'centre_metier_id')) {
            Schema::table('convocation_enseignant', function (Blueprint $table) {
                $table->dropConstrainedForeignId('centre_metier_id');
            });
        }
    }
};
