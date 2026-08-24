<?php

namespace Database\Seeders;

use App\Models\Indemnite\Convocations;
use App\Models\Parametrage\Enseignant;
use Illuminate\Database\Seeder;

/**
 * Membres du jury "ordinaires" (hors chef de centre / president du jury,
 * deja rattaches par ConvocationCentreSeeder) : 4 par convocation — 2
 * correcteurs, 1 surveillant, 1 membre sans fonction speciale — pris dans
 * les 10 DERNIERS enseignants seedes par EnseignantSeeder (le pool
 * "direction" des 10 premiers est reserve aux chefs de centre/presidents
 * de jury, voir ConvocationCentreSeeder). Cette table pivot alimente :
 * - la page "Calcul des indemnités" (fonction = "Correction")
 * - la page "Indemnité de surveillance" (fonction contenant "surveill")
 * - la page "Pièces justificatives" (chaque membre y a son propre dossier)
 *
 * "fonction" est un champ texte libre (voir migration
 * add_fonction_to_convocation_enseignant_table) — pas d'enum a respecter,
 * mais ces 3 valeurs sont celles deja utilisees ailleurs dans le code
 * (FraisDeplacementController, IndemniteCorrectionController,
 * IndemniteSurveillanceController).
 *
 * Doit etre execute APRES ConvocationCentreSeeder (a besoin des centres et
 * de leurs metiers deja crees).
 *
 * Utilisation : php artisan db:seed --class=ConvocationEnseignantSeeder
 */
class ConvocationEnseignantSeeder extends Seeder
{
    public function run(): void
    {
        $membres = Enseignant::orderBy('id')->skip(10)->limit(10)->get()->values();

        if ($membres->count() < 10) {
            $this->command?->warn('ConvocationEnseignantSeeder : moins de 10 enseignants "membres" disponibles — exécutez EnseignantSeeder avant.');

            return;
        }

        $convocations = Convocations::with('centres.metiers')->orderBy('id')->get();

        // Curseur circulaire sur le pool de 10 membres : chaque personne
        // sert donc sur ~4 convocations en moyenne, comme un vrai vivier
        // d'examinateurs reutilise d'une session a l'autre.
        $curseur = 0;
        $prochainMembre = function () use ($membres, &$curseur) {
            $membre = $membres[$curseur % $membres->count()];
            $curseur++;

            return $membre;
        };

        $categoriesPersonnel = ['vacataire', 'contractuel', 'fonctionnaire'];

        foreach ($convocations as $convocation) {
            $centre = $convocation->centres->first();

            if (! $centre) {
                continue;
            }

            $metiers = $centre->metiers;
            $affectations = [
                ['fonction' => 'Correction'],
                ['fonction' => 'Correction'],
                ['fonction' => 'Surveillant'],
                // Membre du jury "simple" : pas de fonction speciale — le
                // front (PiecesJustificativesController::determinerTypeConvocation())
                // retombe alors sur le libelle par defaut "Membre du jury".
                ['fonction' => null],
            ];

            foreach ($affectations as $i => $affectation) {
                $membre = $prochainMembre();
                // Repartit les 4 affectations sur les metiers du centre
                // (1 ou 2) plutot que de toutes les mettre sur le premier.
                $metier = $metiers->isNotEmpty() ? $metiers[$i % $metiers->count()] : null;

                $convocation->enseignants()->attach($membre->id, [
                    'fonction' => $affectation['fonction'],
                    'centre_id' => $centre->id,
                    'centre_metier_id' => $metier?->id,
                    'provenance' => $centre->chef_centre_provenance,
                    'categorie_personnel' => $categoriesPersonnel[array_rand($categoriesPersonnel)],
                ]);
            }
        }
    }
}
