<?php

namespace App\Http\Controllers;

use App\Models\Convocations;
use App\Models\Enseignants;
use Illuminate\Http\Request;

class ConvocationBeneficiaireController extends Controller
{
    /**
     * Afficher les bénéficiaires affectés
     * à une convocation.
     */
    public function index($convocation)
    {
        $convocation = Convocations::findOrFail($convocation);

        $enseignants = $convocation
            ->enseignants()
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Liste des bénéficiaires affectés récupérée avec succès.',
            'data' => $enseignants,
        ], 200);
    }

    /**
     * Rechercher des bénéficiaires.
     */
    public function rechercher(Request $request)
    {
        $request->validate([
            'search' => 'nullable|string|max:255',
        ]);

        $search = $request->input('search');

        $enseignants = Enseignants::query()
            ->when($search, function ($query) use ($search) {
                $query->where('indice', 'like', "%{$search}%");
            })
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Bénéficiaires récupérés avec succès.',
            'data' => $enseignants,
        ], 200);
    }

    /**
     * Affecter plusieurs bénéficiaires
     * à une convocation.
     */
    public function affecter(
        Request $request,
        $convocation
    ) {
        $validated = $request->validate([
            'enseignant_ids' => [
                'required',
                'array',
                'min:1',
            ],

            'enseignant_ids.*' => [
                'required',
                'integer',
                'exists:enseignants,id',
            ],
        ]);

        $convocation = Convocations::findOrFail($convocation);

        // Vérification d'éligibilité
        $enseignants = Enseignants::whereIn(
            'id',
            $validated['enseignant_ids']
        )->get();

        if ($enseignants->count() !== count($validated['enseignant_ids'])) {
            return response()->json([
                'success' => false,
                'message' => 'Un ou plusieurs bénéficiaires sont introuvables.',
            ], 422);
        }

        // Affectation
        $convocation->enseignants()->syncWithoutDetaching(
            $validated['enseignant_ids']
        );

        return response()->json([
            'success' => true,
            'message' => 'Bénéficiaires affectés avec succès.',
            'data' => $convocation
                ->load('enseignants'),
        ], 200);
    }

    /**
     * Retirer un bénéficiaire
     * d'une convocation.
     */
    public function retirer(
        $convocation,
        $enseignant
    ) {
        $convocationModel = Convocations::findOrFail($convocation);

        $enseignantModel = Enseignants::findOrFail($enseignant);

        $convocationModel
            ->enseignants()
            ->detach($enseignantModel->id);

        return response()->json([
            'success' => true,
            'message' => 'Bénéficiaire retiré de la convocation avec succès.',
        ], 200);
    }

    /**
     * Vérifier l'éligibilité d'un bénéficiaire
     * pour une convocation.
     */
    public function verifierEligibilite($convocation, $enseignant)
    {
        // Vérifier que la convocation existe
        $convocationModel = Convocations::findOrFail($convocation);

        // Vérifier que l'enseignant existe
        $enseignantModel = Enseignants::findOrFail($enseignant);

        // Vérifier si l'enseignant est déjà affecté
        // à cette convocation
        $dejaAffecte = $convocationModel
            ->enseignants()
            ->where('enseignants.id', $enseignantModel->id)
            ->exists();

        // Si déjà affecté, il n'est pas éligible
        if ($dejaAffecte) {
            return response()->json([
                'success' => true,
                'eligible' => false,
                'message' => 'Ce bénéficiaire est déjà affecté à cette convocation.',
                'data' => [
                    'convocation_id' => $convocationModel->id,
                    'enseignant_id' => $enseignantModel->id,
                    'eligible' => false,
                ],
            ], 200);
        }

        // Le bénéficiaire existe et n'est pas encore affecté
        return response()->json([
            'success' => true,
            'eligible' => true,
            'message' => 'Le bénéficiaire est éligible pour cette convocation.',
            'data' => [
                'convocation_id' => $convocationModel->id,
                'enseignant_id' => $enseignantModel->id,
                'eligible' => true,
            ],
        ], 200);
    }
}
