<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Le president du jury est, comme le chef de centre, une seule personne
 * rattachee a TOUT le centre (pas a un metier precis) — cf. modele papier
 * "convocation jury" fourni par l'utilisatrice, ligne "President du jury"
 * a cote de "Chef de centre". Meme schema que chef_centre_id/
 * chef_centre_telephone.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('convocation_centres', function (Blueprint $table) {
            if (! Schema::hasColumn('convocation_centres', 'president_jury_id')) {
                $table->foreignId('president_jury_id')
                    ->nullable()
                    ->after('chef_centre_telephone')
                    ->constrained('enseignants')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('convocation_centres', 'president_jury_telephone')) {
                $table->string('president_jury_telephone', 30)
                    ->nullable()
                    ->after('president_jury_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('convocation_centres', function (Blueprint $table) {
            if (Schema::hasColumn('convocation_centres', 'president_jury_id')) {
                $table->dropConstrainedForeignId('president_jury_id');
            }

            if (Schema::hasColumn('convocation_centres', 'president_jury_telephone')) {
                $table->dropColumn('president_jury_telephone');
            }
        });
    }
};
