<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Slug opaque (12 caracteres hex aleatoires) a la place de l'id numerique
 * dans les URLs cote front (show/edit/pdf/centres/metiers...) : evite
 * d'exposer l'id sequentiel (et donc le nombre total de lignes) dans les
 * liens visibles/partageables. L'id numerique reste la cle primaire reelle
 * pour toutes les relations internes (FK, pivot convocation_enseignant...),
 * seul le "visage public" change — voir App\Models\Concerns\HasOpaqueSlug,
 * applique a Convocations, ConvocationCentre et ConvocationCentreMetier.
 */
return new class extends Migration
{
    private const TABLES = ['convocations', 'convocation_centres', 'convocation_centre_metiers'];

    public function up(): void
    {
        foreach (self::TABLES as $table) {
            Schema::table($table, function (Blueprint $blueprint) use ($table) {
                if (! Schema::hasColumn($table, 'slug')) {
                    $blueprint->string('slug', 32)->nullable()->unique()->after('id');
                }
            });
        }

        // Backfill des lignes creees AVANT l'ajout de la colonne : meme
        // generation aleatoire que HasOpaqueSlug (qui ne s'applique qu'a la
        // creation de nouvelles lignes), en verifiant l'unicite au fur et a
        // mesure.
        foreach (self::TABLES as $table) {
            DB::table($table)->whereNull('slug')->chunkById(200, function ($lignes) use ($table) {
                foreach ($lignes as $ligne) {
                    do {
                        $slug = bin2hex(random_bytes(6));
                    } while (DB::table($table)->where('slug', $slug)->exists());

                    DB::table($table)->where('id', $ligne->id)->update(['slug' => $slug]);
                }
            });
        }
    }

    public function down(): void
    {
        foreach (self::TABLES as $table) {
            Schema::table($table, function (Blueprint $blueprint) use ($table) {
                if (Schema::hasColumn($table, 'slug')) {
                    $blueprint->dropColumn('slug');
                }
            });
        }
    }
};
