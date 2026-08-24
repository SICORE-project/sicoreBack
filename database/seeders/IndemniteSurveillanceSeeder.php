<?php

namespace Database\Seeders;

use App\Models\Indemnite\Convocations;
use App\Models\Indemnite\IndemniteSurveillance;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Une indemnité de surveillance par membre affecté à la fonction
 * "Surveillant" (voir ConvocationEnseignantSeeder) — même principe de
 * cohérence que IndemniteCorrectionSeeder (statut aligné sur celui de la
 * convocation, rien pour une convocation encore en brouillon).
 *
 * Doit être exécuté APRÈS ConvocationEnseignantSeeder.
 *
 * Utilisation : php artisan db:seed --class=IndemniteSurveillanceSeeder
 */
class IndemniteSurveillanceSeeder extends Seeder
{
    private const TARIF_HORAIRE = 2000.00;

    public function run(): void
    {
        $convocations = Convocations::with('centres.metiers')->orderBy('id')->get()->keyBy('id');

        $membres = DB::table('convocation_enseignant')
            ->where('fonction', 'Surveillant')
            ->get();

        foreach ($membres as $membre) {
            $convocation = $convocations->get($membre->convocation_id);

            if (! $convocation || $convocation->statut === 'brouillon') {
                continue;
            }

            $centre = $convocation->centres->firstWhere('id', $membre->centre_id);
            $metier = $centre?->metiers->firstWhere('id', $membre->centre_metier_id);

            // Heures par demi-journee de surveillance (4h), sur 1 a 3
            // demi-journees selon la duree de la session.
            $nombreHeures = random_int(1, 3) * 4.0;
            $montant = $nombreHeures * self::TARIF_HORAIRE;

            IndemniteSurveillance::create([
                'convocation_id' => $membre->convocation_id,
                'convocation_centre_id' => $membre->centre_id,
                'enseignant_id' => $membre->enseignant_id,
                'metier' => $metier->metier ?? ($centre->metier ?? 'Surveillance générale'),
                'nombre_heures' => $nombreHeures,
                'tarif_horaire' => self::TARIF_HORAIRE,
                'montant' => $montant,
                'statut' => in_array($convocation->statut, ['cloturee', 'envoyee'], true) ? 'valide' : 'calcule',
            ]);
        }
    }
}
