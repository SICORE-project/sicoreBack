<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Table `indemnites` générique — reprend, pour référence, les indemnités
 * de correction et de surveillance déjà calculées par IndemniteCorrectionSeeder/
 * IndemniteSurveillanceSeeder (une ligne "générique" par ligne "spécialisée").
 *
 * Ecrit en DB::table() plutôt que via le modèle Eloquent : comme
 * `App\Models\type_indemnites` (voir TypeIndemniteSeeder), la classe
 * `App\Models\indemnites` vit dans app/Models/indemnite/ mais déclare
 * `namespace App\Models;` — décalage avec l'autoload PSR-4 standard qui la
 * rend indisponible tant qu'un classmap Composer ne l'a pas répertoriée.
 *
 * NB pour l'utilisatrice, important : cette table n'est actuellement
 * affichée par AUCUNE page du front déjà construit — les pages "Calcul"/
 * "Calcul-surveillance" utilisent `indemnites_correction`/
 * `indemnites_surveillance` (déjà remplies), pas celle-ci. Elle est
 * seedée ici uniquement parce que demandé explicitement et pour rester
 * cohérente avec ces deux tables si un écran venait un jour à l'exploiter
 * — ne vous attendez pas à la voir apparaître dans l'interface.
 *
 * Doit être exécuté APRÈS TypeIndemniteSeeder, IndemniteCorrectionSeeder
 * ET IndemniteSurveillanceSeeder.
 *
 * Utilisation : php artisan db:seed --class=IndemniteSeeder
 */
class IndemniteSeeder extends Seeder
{
    private const UTILISATEUR_ID = 1;

    public function run(): void
    {
        $typeCorrectionId = DB::table('type_indemnites')->where('libelle', 'Indemnité de correction')->value('id');
        $typeSurveillanceId = DB::table('type_indemnites')->where('libelle', 'Indemnité de surveillance')->value('id');

        if (! $typeCorrectionId || ! $typeSurveillanceId) {
            $this->command?->warn('IndemniteSeeder : exécutez TypeIndemniteSeeder avant.');

            return;
        }

        foreach (DB::table('indemnites_correction')->get() as $ligne) {
            $this->creerIndemnite($typeCorrectionId, $ligne, nombreCopies: $ligne->nombre_copies);
        }

        foreach (DB::table('indemnites_surveillance')->get() as $ligne) {
            $this->creerIndemnite($typeSurveillanceId, $ligne, nombreHeures: $ligne->nombre_heures);
        }
    }

    private function creerIndemnite(
        int $typeIndemniteId,
        object $ligne,
        ?int $nombreCopies = null,
        ?float $nombreHeures = null,
    ): void {
        $maintenant = now();

        DB::table('indemnites')->insert([
            'utilisateur_id' => self::UTILISATEUR_ID,
            'type_indemnite_id' => $typeIndemniteId,
            'convocation_id' => $ligne->convocation_id,
            'enseignant_id' => $ligne->enseignant_id,
            'montant_base' => $ligne->montant,
            'frais_deplacement' => 0,
            'montant_total' => $ligne->montant,
            'statut' => $ligne->statut,
            'nombre_copies' => $nombreCopies,
            'nombre_heures' => $nombreHeures,
            'valide_par' => $ligne->statut === 'valide' ? self::UTILISATEUR_ID : null,
            'valide_at' => $ligne->statut === 'valide' ? $maintenant : null,
            'created_at' => $maintenant,
            'updated_at' => $maintenant,
        ]);
    }
}
