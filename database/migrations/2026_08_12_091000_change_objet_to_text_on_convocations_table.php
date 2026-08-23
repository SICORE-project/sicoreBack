<?php

use Illuminate\Database\Migrations\Migration;
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
        if (Schema::hasTable('convocations')) {
            // "NULL" permet de passer le mode strict de MySQL lors du rafraîchissement des tests
            DB::statement('ALTER TABLE convocations MODIFY objet TEXT NULL');
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('convocations')) {
            DB::statement("ALTER TABLE convocations MODIFY objet VARCHAR(255) NOT NULL DEFAULT ''");
        }
    }
};
