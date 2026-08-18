<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Complète la migration 2026_08_18_004500 : en comparant à nouveau, terme à
 * terme, le formulaire au document papier ("PREND EXACTEMENT CE QUI EST
 * DANS LE DOCUMENT" — demande utilisatrice), 3 champs du RECTO manquaient
 * encore :
 *  - "en date du [...] de [...]" : le "de" final après la date de l'ordre
 *    de service (ligne distincte de "Suivant ordre de service N°", pas
 *    reprise la première fois) -> ordre_service_emetteur
 *  - "ARRETE à la somme de :" sous le tableau des avances (texte/montant en
 *    toutes lettres, distinct du TOTAL calculé) -> arrete_somme
 *  - "Fait à Dakar le ... 20.." en bas du tableau des avances (distinct du
 *    "Dakar le" déjà ajouté juste après "Délivré par nous (4)" : sur le
 *    papier il y a bien DEUX dates "Dakar le", une pour la délivrance de la
 *    fiche, une pour l'arrêté du décompte) -> date_fait_avance
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('missions_deplacement', function (Blueprint $table) {
            $table->string('ordre_service_emetteur', 255)->nullable()->after('ordre_service_date');
            $table->string('arrete_somme', 255)->nullable()->after('avance_total');
            $table->date('date_fait_avance')->nullable()->after('avance_versee');
        });
    }

    public function down(): void
    {
        Schema::table('missions_deplacement', function (Blueprint $table) {
            $table->dropColumn(['ordre_service_emetteur', 'arrete_somme', 'date_fait_avance']);
        });
    }
};
