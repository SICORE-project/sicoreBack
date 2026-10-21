<?php

namespace App\Http\Controllers\Api\Indemnites;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\Indemnites\Concerns\ApiResponseTrait;
use App\Models\Indemnite\Convocations as ConvocationModel;
use App\Models\Indemnite\IndemniteCorrection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Indemnités de correction : montant = nombre de copies corrigées x taux
 * par copie, pour les membres ayant la fonction "Correction" — pas de
 * "fiche" à remplir (contrairement à Frais de déplacement), un simple
 * enregistrement calculé (voir IndemniteCorrection). Le taux est saisi
 * librement à chaque calcul (varie par métier), pas de barème préconfiguré.
 */
class IndemniteCorrectionController extends Controller
{
    use ApiResponseTrait;

    /**
     * Membres "Correction" d'une convocation, groupés implicitement par
     * centre puis métier (chaque ligne porte 'centre'/'centre_id'/'metier').
     * $centre_id (optionnel) restreint à un seul centre — c'est ainsi que le
     * "Calcul groupé" est utilisé (demande utilisatrice : "pour tout les
     * membres qui sont dans le meme centre dans la meme convocation").
     * Indique aussi, par ligne, l'indemnité déjà calculée le cas échéant
     * (évite les doublons côté front).
     */
    public function correcteursEligibles(Request $request)
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

        $indemnitesExistantes = IndemniteCorrection::where('convocation_id', $convocationId)
            ->get()
            ->keyBy(fn (IndemniteCorrection $i) => $i->enseignant_id.'|'.$i->metier);

        $lignes = collect();

        foreach ($centres as $centre) {
            foreach ($centre->metiers as $metierGroupe) {
                foreach ($metierGroupe->enseignants as $enseignant) {
                    $fonction = $enseignant->pivot->fonction ?? '';

                    // Seule la fonction "Correction" concerne cette page —
                    // meme detection tolerante (str_contains, insensible a
                    // la casse) que PiecesJustificativesController::
                    // determinerTypeConvocation() cote front.
                    if (! Str::contains(Str::lower($fonction), 'correct')) {
                        continue;
                    }

                    $existante = $indemnitesExistantes->get($enseignant->id.'|'.$metierGroupe->metier);

                    $lignes->push([
                        'id' => $enseignant->id,
                        'nom' => $enseignant->nom,
                        'prenom' => $enseignant->prenom,
                        'matricule' => $enseignant->matricule,
                        'centre' => $centre->centre,
                        'centre_id' => $centre->id,
                        'metier' => $metierGroupe->metier,
                        'indemnite_correction_id' => $existante?->id,
                        'nombre_copies' => $existante?->nombre_copies,
                        'taux_copie' => $existante?->taux_copie,
                        'montant' => $existante?->montant,
                        'statut' => $existante?->statut,
                    ]);
                }
            }
        }

        return $this->success('Correcteurs éligibles.', $lignes->values());
    }

    /**
     * Création en masse — une indemnité par (enseignant, métier) coché,
     * réutilisable pour un ou plusieurs métiers/correcteurs d'un même
     * centre en un seul appel (voir calcul-groupe.blade.php côté front).
     */
    public function calculerGroupe(Request $request)
    {
        $data = $request->validate([
            'convocation_id' => 'required|integer|exists:convocations,id',
            'lignes' => 'required|array|min:1',
            'lignes.*.enseignant_id' => 'required|integer|exists:enseignants,id',
            'lignes.*.convocation_centre_id' => 'required|integer|exists:convocation_centres,id',
            'lignes.*.metier' => 'required|string',
            'lignes.*.nombre_copies' => 'required|integer|min:1',
            'lignes.*.taux_copie' => 'required|numeric|min:0',
        ]);

        $creees = 0;
        $erreurs = [];

        DB::transaction(function () use ($data, &$creees, &$erreurs) {
            foreach ($data['lignes'] as $ligne) {
                $existe = IndemniteCorrection::where('convocation_id', $data['convocation_id'])
                    ->where('enseignant_id', $ligne['enseignant_id'])
                    ->where('metier', $ligne['metier'])
                    ->exists();

                if ($existe) {
                    $erreurs[] = "Une indemnité de correction existe déjà pour l'enseignant #{$ligne['enseignant_id']} sur le métier « {$ligne['metier']} ».";

                    continue;
                }

                IndemniteCorrection::create([
                    'convocation_id' => $data['convocation_id'],
                    'convocation_centre_id' => $ligne['convocation_centre_id'],
                    'enseignant_id' => $ligne['enseignant_id'],
                    'metier' => $ligne['metier'],
                    'nombre_copies' => $ligne['nombre_copies'],
                    'taux_copie' => $ligne['taux_copie'],
                    'montant' => $ligne['nombre_copies'] * $ligne['taux_copie'],
                    'statut' => 'calcule',
                ]);

                $creees++;
            }
        });

        return $this->success(
            $creees > 0 ? "{$creees} indemnité(s) de correction créée(s)." : "Aucune indemnité n'a pu être créée.",
            ['creees' => $creees, 'erreurs' => $erreurs]
        );
    }
}
