<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Meme logique que "fonction" (migration 2026_08_10_100000) : la
 * provenance ("LTP-FXN/THIES", "LTID"...) affichee dans formconf.jpeg est
 * propre a CETTE convocation, pas un attribut fixe de l'enseignant - elle
 * vit donc sur le pivot, pas sur `enseignants`.
 *
 * Le champ existe deja dans le formulaire front (data-member-provenance)
 * mais n'etait jusqu'ici relaye nulle part cote back.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('convocation_enseignant', 'provenance')) {
            Schema::table('convocation_enseignant', function (Blueprint $table) {
                $table->string('provenance', 255)->nullable()->after('centre_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('convocation_enseignant', 'provenance')) {
            Schema::table('convocation_enseignant', function (Blueprint $table) {
                $table->dropColumn('provenance');
            });
        }
    }
};
