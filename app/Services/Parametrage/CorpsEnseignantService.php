<?php

namespace App\Http\Controllers\Api\Parametrage;

use App\Http\Controllers\Controller;
use App\Http\Requests\Parametrage\StoreCorpsEnseignantRequest;
use App\Http\Requests\Parametrage\UpdateCorpsEnseignantRequest;
use App\Services\Parametrage\CorpsEnseignantService;

class CorpsController extends Controller
{
    public function __construct(
        private CorpsEnseignantService $corpsService
    ) {
    }

    public function index()
    {
        $corps = $this->corpsService->getAll();

        return response()->json([
            'message' => 'Liste des corps enseignants récupérée avec succès.',
            'data' => $corps,
        ], 200);
    }

    public function store(StoreCorpsEnseignantRequest $request)
    {
        $corps = $this->corpsService->create(
            $request->validated()
        );

        return response()->json([
            'message' => 'Corps enseignant créé avec succès.',
            'data' => $corps,
        ], 201);
    }

    public function show($id)
    {
        $corps = $this->corpsService->findById($id);

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

    public function update(
        UpdateCorpsEnseignantRequest $request,
        $id
    ) {
        $corps = $this->corpsService->findById($id);

        if (!$corps) {
            return response()->json([
                'message' => 'Corps enseignant introuvable.',
            ], 404);
        }

        $corps = $this->corpsService->update(
            $corps,
            $request->validated()
        );

        return response()->json([
            'message' => 'Corps enseignant modifié avec succès.',
            'data' => $corps,
        ], 200);
    }

    public function destroy($id)
    {
        $corps = $this->corpsService->findById($id);

        if (!$corps) {
            return response()->json([
                'message' => 'Corps enseignant introuvable.',
            ], 404);
        }

        $this->corpsService->delete($corps);

        return response()->json([
            'message' => 'Corps enseignant supprimé avec succès.',
        ], 200);
    }
}