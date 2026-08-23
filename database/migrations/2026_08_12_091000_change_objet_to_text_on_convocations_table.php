<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * `objet` est un varchar(255). Le paragraphe imprimé sur formconf.jpeg
 * ("Les personnes dont les noms suivent, sont désignées membres de jury...
 * ...à partir de 8 heures précises dans les différents centres conformément
 * au tableau ci-dessous.") dépasse déjà 255 caractères à lui seul.
 *
 * Utilise une requête SQL brute plutôt que ->change() (doctrine/dbal non
 * installé, cf. les autres migrations de cette série).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE convocations ALTER COLUMN objet TYPE TEXT');
            DB::statement('ALTER TABLE convocations ALTER COLUMN objet SET NOT NULL');

            return;
        }

        DB::statement('ALTER TABLE convocations MODIFY objet TEXT NOT NULL');
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE convocations ALTER COLUMN objet TYPE VARCHAR(255) USING LEFT(objet, 255)');
            DB::statement("ALTER TABLE convocations ALTER COLUMN objet SET DEFAULT ''");
            DB::statement('ALTER TABLE convocations ALTER COLUMN objet SET NOT NULL');

            return;
        }

        DB::statement("ALTER TABLE convocations MODIFY objet VARCHAR(255) NOT NULL DEFAULT ''");
    }
};
