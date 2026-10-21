<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Doit être migrée après create_types_convocation_table.
 *
 * La colonne est créée nullable puis backfillée vers "jury_examen" pour
 * toutes les convocations déjà en base (c'était le seul cas géré jusqu'ici,
 * cf. formconf.jpeg).
 *
 * NOTE : reste nullable en base (pas de ->change(), qui nécessiterait
 * doctrine/dbal — absent de composer.json). Le caractère obligatoire est
 * imposé côté applicatif dans StoreConvocationRequest/UpdateConvocationRequest
 * (cf. proposal.md).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('convocations', 'type_convocation_id')) {
            return;
        }

        Schema::table('convocations', function (Blueprint $table) {
            $table->foreignId('type_convocation_id')
                ->nullable()
                ->after('id')
                ->constrained('types_convocation')
                ->restrictOnDelete();
        });

        $juryExamenId = DB::table('types_convocation')->where('code', 'jury_examen')->value('id');

        if ($juryExamenId) {
            DB::table('convocations')
                ->whereNull('type_convocation_id')
                ->update(['type_convocation_id' => $juryExamenId]);
        }

        Schema::table('convocations', function (Blueprint $table) {
            $table->index('type_convocation_id');
        });
    }

    public function down(): void
    {
        Schema::table('convocations', function (Blueprint $table) {
            $table->dropForeign(['type_convocation_id']);
            $table->dropColumn('type_convocation_id');
        });
    }
};
