<?php

namespace Database\Seeders;

use App\Models\Indemnite\Convocations;
use App\Models\Indemnite\IndemniteCorrection;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Une indemnité de correction par membre affecté à la fonction
 * "Correction" (voir ConvocationEnseignantSeeder) — alimente la page
 * "Calcul des indemnités" avec de vrais montants déjà calculés, cohérents
 * avec le statut de la convocation : une session clôturée a ses
 * indemnités "validées", une session encore émise les a "calculées"
 * (pas encore validées) — pas de correction calculée pour une convocation
 * encore en brouillon (le jury n'a pas encore corrigé quoi que ce soit).
 *
 * Doit être exécuté APRÈS ConvocationEnseignantSeeder.
 *
 * Utilisation : php artisan db:seed --class=IndemniteCorrectionSeeder
 */
class IndemniteCorrectionSeeder extends Seeder
{
    private const TAUX_COPIE = 500.00;

    public function run(): void
    {
        $convocations = Convocations::with('centres.metiers')->orderBy('id')->get()->keyBy('id');

        $membres = DB::table('convocation_enseignant')
            ->where('fonction', 'Correction')
            ->get();

        foreach ($membres as $membre) {
            $convocation = $convocations->get($membre->convocation_id);

            // Le jury n'a pas encore corrige de copies tant que la
            // convocation reste en brouillon.
            if (! $convocation || $convocation->statut === 'brouillon') {
                continue;
            }

            $centre = $convocation->centres->firstWhere('id', $membre->centre_id);
            $metier = $centre?->metiers->firstWhere('id', $membre->centre_metier_id);

            $nombreCopies = random_int(20, 80);
            $montant = $nombreCopies * self::TAUX_COPIE;

            IndemniteCorrection::create([
                'convocation_id' => $membre->convocation_id,
                'convocation_centre_id' => $membre->centre_id,
                'enseignant_id' => $membre->enseignant_id,
                'metier' => $metier->metier ?? ($centre->metier ?? 'Correction générale'),
                'nombre_copies' => $nombreCopies,
                'taux_copie' => self::TAUX_COPIE,
                'montant' => $montant,
                'statut' => $convocation->statut === 'envoyee' ? 'valide' : 'calcule',
            ]);
        }
    }
}
