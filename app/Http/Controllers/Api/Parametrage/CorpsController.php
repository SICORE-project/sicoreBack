<?php

namespace App\Http\Controllers\Api\Parametrage;

use App\Http\Controllers\Controller;
use App\Models\Parametrage\CorpsEnseignant;
use Illuminate\Http\Request;

class CorpsController extends Controller
{
    /**
     * Liste des corps enseignants
     */
    public function index()
    {
        $corps = CorpsEnseignant::with('categorie')
            ->orderBy('libelle')
            ->get();

        return response()->json([
            'message' => 'Liste des corps enseignants récupérée avec succès.',
            'data' => $corps,
        ], 200);
    }

    /**
     * Création d'un corps enseignant
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'code' => [
                'required',
                'string',
                'max:50',
                'unique:corps_enseignant,code',
            ],

            'libelle' => [
                'required',
                'string',
                'max:255',
            ],

            

            'description' => [
                'nullable',
                'string',
            ],
        ]);

        $corps = CorpsEnseignant::create($data);

        return response()->json([
            'message' => 'Corps enseignant créé avec succès.',
            'data' => $corps,
        ], 201);
    }

    /**
     * Afficher un corps enseignant
     */
    public function show($id)
    {
        $corps = CorpsEnseignant::with('categorie')->find($id);

        if (!$corps) {
            return response()->json([
                'message' => 'Corps enseignant introuvable.',
            ], 404);
        }

        return response()->json([
            'message' => 'Corps enseignant récupéré avec succès.',
            'data' => $corps,
        ], 200);
    }

    /**
     * Modifier un corps enseignant
     */
    public function update(Request $request, $id)
    {
        $corps = CorpsEnseignant::find($id);

        if (!$corps) {
            return response()->json([
                'message' => 'Corps enseignant introuvable.',
            ], 404);
        }

        $data = $request->validate([
            'code' => [
                'required',
                'string',
                'max:50',
                'unique:corps_enseignant,code,' . $corps->id,
            ],

            'libelle' => [
                'required',
                'string',
                'max:255',
            ],

           

            'description' => [
                'nullable',
                'string',
            ],
        ]);

        $corps->update($data);

        return response()->json([
            'message' => 'Corps enseignant modifié avec succès.',
            'data' => $corps->fresh('categorie'),
        ], 200);
    }

    /**
     * Suppression d'un corps enseignant
     */
    public function destroy($id)
    {
        $corps = CorpsEnseignant::find($id);

        if (!$corps) {
            return response()->json([
                'message' => 'Corps enseignant introuvable.',
            ], 404);
        }

        $corps->delete();

        return response()->json([
            'message' => 'Corps enseignant supprimé avec succès.',
        ], 200);
    }
}