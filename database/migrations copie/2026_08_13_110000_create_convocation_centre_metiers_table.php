<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Un centre d'examen peut couvrir plusieurs metiers/specialites (cf.
 * modele papier "convocation jury BT" : un meme centre, un meme jury, un
 * meme chef de centre, mais plusieurs metiers, chacun avec ses propres
 * membres du jury). Jusqu'ici "metier" etait un simple champ texte unique
 * sur convocation_centres — cette table le remplace par une vraie relation
 * un centre -> plusieurs metiers (voir aussi
 * add_centre_metier_id_to_convocation_enseignant_table, qui migre les
 * donnees existantes vers ce nouveau modele).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('convocation_centre_metiers')) {
            return;
        }

        Schema::create('convocation_centre_metiers', function (Blueprint $table) {
            $table->id();

            $table->foreignId('convocation_centre_id')->constrained('convocation_centres')->cascadeOnDelete();

            $table->string('metier', 255);

            $table->timestamps();

            $table->index('convocation_centre_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('convocation_centre_metiers');
    }
};
