<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Le chef de centre et le président du jury sont rattachés à un centre
 * (chef_centre_id/president_jury_id, colonnes existantes) mais n'ont
 * jamais eu de "provenance" (lieu où ils exercent habituellement) —
 * contrairement aux membres du jury, qui l'ont via
 * convocation_enseignant.provenance. Sans elle, la comparaison
 * provenance/lieu d'affectation du calcul des frais de déplacement
 * (FraisDeplacementController) ne pouvait jamais s'appliquer pour ces
 * deux rôles (toujours "taux plein", jamais de ÷4 possible).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('convocation_centres', function (Blueprint $table) {
            if (! Schema::hasColumn('convocation_centres', 'chef_centre_provenance')) {
                $table->string('chef_centre_provenance', 255)->nullable()->after('chef_centre_telephone');
            }

            if (! Schema::hasColumn('convocation_centres', 'president_jury_provenance')) {
                $table->string('president_jury_provenance', 255)->nullable()->after('president_jury_telephone');
            }
        });
    }

    public function down(): void
    {
        Schema::table('convocation_centres', function (Blueprint $table) {
            $table->dropColumn(['chef_centre_provenance', 'president_jury_provenance']);
        });
    }
};
