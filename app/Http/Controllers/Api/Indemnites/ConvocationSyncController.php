<?php

namespace App\Http\Controllers\Api\Indemnites;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\Indemnites\Concerns\ApiResponseTrait;
use App\Http\Requests\Indemnites\SyncConvocationStructureRequest;
use App\Models\Indemnite\Convocations as ConvocationModel;
use App\Models\Parametrage\Enseignant;
use Illuminate\Support\Facades\DB;

/**
 * Fiche "Modifier" alignee sur l'assistant de creation : UN seul
 * enregistrement remplace toute la structure de la convocation (infos
 * generales + centres + leurs metiers + membres du jury), au lieu des
 * nombreux petits formulaires "Ajouter/Modifier/Supprimer" independants
 * utilises jusque-la sur la fiche d'edition. Voir SyncConvocationStructureRequest
 * pour le detail du format attendu.
 */
class ConvocationSyncController extends Controller
{
    use ApiResponseTrait;

    public function sync(SyncConvocationStructureRequest $request, string $id)
    {
        $convocation = ConvocationModel::trouverParSlugOuId($id);

        if (! $convocation) {
            return $this->error('Convocation introuvable.', 404);
        }

        $donnees = $request->validated();

        $convocation = DB::transaction(function () use ($convocation, $donnees) {
            $convocation->update(collect($donnees)->only([
                'type_convocation_id',
                'date_emission',
                'date_debut',
                'date_fin',
                'heure_debut',
                'objet',
                'session',
                'lieu_examen',
                'ordre_de_mission',
                'lieu_affectation',
                'statut',
            ])->all());

            $centresSoumisIds = [];
            $centreIdParIndex = [];
            $metierIdParIndexParCentre = [];

            foreach (($donnees['centres'] ?? []) as $centreIndex => $centreDonnees) {
                $metiersDonnees = $centreDonnees['metiers'] ?? [];
                $centreId = $centreDonnees['id'] ?? null;

                $champsCentre = collect($centreDonnees)->only([
                    'centre', 'jury', 'chef_centre_id', 'chef_centre_telephone',
                    'president_jury_id', 'president_jury_telephone',
                ])->all();

                if ($centreId && $convocation->centres()->where('id', $centreId)->exists()) {
                    $centre = $convocation->centres()->find($centreId);
                    $centre->update($champsCentre);
                } else {
                    $centre = $convocation->centres()->create($champsCentre);
                }

                $centresSoumisIds[] = $centre->id;
                $centreIdParIndex[$centreIndex] = $centre->id;

                $metiersSoumisIds = [];

                foreach ($metiersDonnees as $metierIndex => $metierDonnees) {
                    $nomMetier = $metierDonnees['metier'] ?? null;

                    // Groupe "general" du wizard (sans nom de metier) : pas
                    // de ligne convocation_centre_metiers a creer, ses
                    // membres restent sans centre_metier_id — regroupes
                    // sous "Non classés" cote affichage (voir
                    // grouperBeneficiairesParMetier() cote front).
                    if (empty($nomMetier)) {
                        continue;
                    }

                    $metierId = $metierDonnees['id'] ?? null;

                    if ($metierId && $centre->metiers()->where('id', $metierId)->exists()) {
                        $metier = $centre->metiers()->find($metierId);
                        $metier->update(['metier' => $nomMetier]);
                    } else {
                        $metier = $centre->metiers()->create(['metier' => $nomMetier]);
                    }

                    $metiersSoumisIds[] = $metier->id;
                    $metierIdParIndexParCentre[$centreIndex][$metierIndex] = $metier->id;
                }

                $centre->metiers()->whereNotIn('id', $metiersSoumisIds)->delete();
            }

            
            $convocation->centres()->whereNotIn('id', $centresSoumisIds)->delete();

            // Membres du jury : remplacement complet (sync(), pas
            // syncWithoutDetaching()) — un enseignant retire du wizard
            // avant l'enregistrement doit disparaitre de la convocation.
            $sync = [];

            foreach (($donnees['beneficiaires'] ?? []) as $beneficiaire) {
                $centreIndex = $beneficiaire['centre_index'] ?? null;
                $metierIndex = $beneficiaire['metier_index'] ?? null;

                $centreId = $centreIndex !== null ? ($centreIdParIndex[$centreIndex] ?? null) : null;
                $centreMetierId = ($centreIndex !== null && $metierIndex !== null)
                    ? ($metierIdParIndexParCentre[$centreIndex][$metierIndex] ?? null)
                    : null;

                $sync[$beneficiaire['enseignant_id']] = [
                    'fonction' => $beneficiaire['fonction'] ?? null,
                    'provenance' => $beneficiaire['provenance'] ?? null,
                    'centre_id' => $centreId,
                    'centre_metier_id' => $centreMetierId,
                ];

                if (! empty($beneficiaire['categorie_personnel'])) {
                    Enseignant::where('id', $beneficiaire['enseignant_id'])
                        ->update(['categorie_personnel' => $beneficiaire['categorie_personnel']]);
                }
            }

            $convocation->enseignants()->sync($sync);

            return $convocation->fresh(['centres.chefCentre', 'centres.presidentJury', 'centres.metiers', 'enseignants']);
        });

        return $this->success('Convocation mise à jour avec succès.', $convocation);
    }
}
