<?php

namespace App\Http\Controllers\Api\Indemnites;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\Indemnites\Concerns\ApiResponseTrait;
use App\Models\Indemnite\Convocations as ConvocationModel;
use App\Models\Indemnite\IndemniteSurveillance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class IndemniteSurveillanceController extends Controller
{
    use ApiResponseTrait;

    public function surveillantsEligibles(Request $request)
    {
        $convocationId = $request->query('convocation_id');

        if (! $convocationId) {
            return $this->error('convocation_id est requis.', 422);
        }

        $convocation = ConvocationModel::with(['centres.metiers.enseignants'])->find($convocationId);

        if (! $convocation) {
            return $this->error('Convocation introuvable.', 404);
        }

        $centreId = $request->query('centre_id');
        $centres = $centreId
            ? $convocation->centres->where('id', $centreId)
            : $convocation->centres;

        $indemnitesExistantes = IndemniteSurveillance::where('convocation_id', $convocationId)
            ->get()
            ->keyBy(fn (IndemniteSurveillance $i) => $i->enseignant_id.'|'.$i->metier);

        $lignes = collect();

        foreach ($centres as $centre) {
            foreach ($centre->metiers as $metierGroupe) {
                foreach ($metierGroupe->enseignants as $enseignant) {
                    $fonction = $enseignant->pivot->fonction ?? '';

                    if (! Str::contains(Str::lower($fonction), 'surveill')) {
                        continue;
                    }

                    $existante = $indemnitesExistantes->get($enseignant->id.'|'.$metierGroupe->metier);

                    $lignes->push([
                        'id' => $enseignant->id,
                        'nom' => $enseignant->nom,
                        'prenom' => $enseignant->prenom,
                        'matricule' => $enseignant->matricule,
                        'categorie_personnel' => $enseignant->pivot->categorie_personnel ?: $enseignant->categorie_personnel,
                        'fonction' => $enseignant->pivot->fonction ?: 'Surveillance',
                        'centre' => $centre->centre,
                        'centre_id' => $centre->id,
                        'metier' => $metierGroupe->metier,
                        'indemnite_surveillance_id' => $existante?->id,
                        'nombre_heures' => $existante?->nombre_heures,
                        'tarif_horaire' => $existante?->tarif_horaire,
                        'montant' => $existante?->montant,
                        'statut' => $existante?->statut,
                    ]);
                }
            }
        }

        return $this->success('Surveillants éligibles.', $lignes->values());
    }

    public function calculerGroupe(Request $request)
    {
        $data = $request->validate([
            'convocation_id' => 'required|integer|exists:convocations,id',
            'lignes' => 'required|array|min:1',
            'lignes.*.enseignant_id' => 'required|integer|exists:enseignants,id',
            'lignes.*.convocation_centre_id' => 'required|integer|exists:convocation_centres,id',
            'lignes.*.metier' => 'required|string',
            'lignes.*.nombre_heures' => 'required|numeric|min:0.5',
            'lignes.*.tarif_horaire' => 'required|numeric|min:0',
        ]);

        $creees = 0;
        $erreurs = [];

        DB::transaction(function () use ($data, &$creees, &$erreurs) {
            foreach ($data['lignes'] as $ligne) {
                $existe = IndemniteSurveillance::where('convocation_id', $data['convocation_id'])
                    ->where('enseignant_id', $ligne['enseignant_id'])
                    ->where('metier', $ligne['metier'])
                    ->exists();

                if ($existe) {
                    $erreurs[] = "Une indemnité de surveillance existe déjà pour l'enseignant #{$ligne['enseignant_id']} sur le métier « {$ligne['metier']} ».";

                    continue;
                }

                IndemniteSurveillance::create([
                    'convocation_id' => $data['convocation_id'],
                    'convocation_centre_id' => $ligne['convocation_centre_id'],
                    'enseignant_id' => $ligne['enseignant_id'],
                    'metier' => $ligne['metier'],
                    'nombre_heures' => $ligne['nombre_heures'],
                    'tarif_horaire' => $ligne['tarif_horaire'],
                    'montant' => $ligne['nombre_heures'] * $ligne['tarif_horaire'],
                    'statut' => 'calcule',
                ]);

                $creees++;
            }
        });

        return $this->success(
            $creees > 0 ? "{$creees} indemnité(s) de surveillance créée(s)." : "Aucune indemnité n'a pu être créée.",
            ['creees' => $creees, 'erreurs' => $erreurs]
        );
    }
}
