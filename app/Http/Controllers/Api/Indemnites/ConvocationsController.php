<?php

namespace App\Http\Controllers;

use App\Models\Convocations;
use Illuminate\Http\Request;

class ConvocationsController extends Controller
{
    /**
     * Afficher la liste de toutes les convocations.
     *
     * GET /api/convocations
     */
    public function index()
    {
        $convocations = Convocations::orderBy('id', 'desc')->get();

        return response()->json([
            'success' => true,
            'message' => 'Liste des convocations récupérée avec succès.',
            'data' => $convocations,
        ], 200);
    }

    /**
     * Créer une nouvelle convocation.
     *
     * POST /api/convocations
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'date_emission' => [
                'required',
                'date',
            ],

            'objet' => [
                'required',
                'string',
                'max:255',
            ],

            'statut' => ['nullable', 'in:en_attente,validee,annulee'],

            'lieu_examen' => [
                'required',
                'string',
                'max:255',
            ],

            'ordre_de_mission' => [
                'nullable',
                'boolean',
            ],

            'lieu_affectation' => [
                'nullable',
                'string',
                'max:255',
            ],

        ]);

        $convocation = Convocations::create([
            'date_emission' => $validated['date_emission'],
            'objet' => $validated['objet'],
            'statut' => $validated['statut'] ?? 'en_attente',
            'lieu_examen' => $validated['lieu_examen'],
            'ordre_de_mission' => $validated['ordre_de_mission'] ?? false,
            'lieu_affectation' => $validated['lieu_affectation'] ?? null,
            'utilisateur_id' => $request->user()->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Convocation créée avec succès.',
            'data' => $convocation,
        ], 201);
    }

    /**
     * Afficher une convocation précise.
     *
     * GET /api/convocations/{id}
     */
    public function show(Convocations $convocation)
    {
        return response()->json([
            'success' => true,
            'message' => 'Convocation récupérée avec succès.',
            'data' => $convocation,
        ], 200);
    }

    /**
     * Modifier une convocation.
     *
     * PUT /api/convocations/{id}
     */
    public function update(Request $request, Convocations $convocation)
    {
        $validated = $request->validate([
            'date_emission' => [
                'sometimes',
                'required',
                'date',
            ],

            'objet' => [
                'sometimes',
                'required',
                'string',
                'max:255',
            ],

            'statut' => [
                'sometimes',
                'required',
                'in:en_attente,validee,annulee,envoyee',
            ],

            'lieu_examen' => [
                'sometimes',
                'required',
                'string',
                'max:255',
            ],

            'ordre_de_mission' => [
                'sometimes',
                'required',
                'boolean',
            ],

            'lieu_affectation' => [
                'nullable',
                'string',
                'max:255',
            ],

        ]);

        $convocation->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Convocation modifiée avec succès.',
            'data' => $convocation->fresh(),
        ], 200);
    }

    /**
     * Supprimer une convocation.
     *
     * DELETE /api/convocations/{id}
     */
    public function destroy(Convocations $convocation)
    {
        $convocation->delete();

        return response()->json([
            'success' => true,
            'message' => 'Convocation supprimée avec succès.',
        ], 200);
    }
}
