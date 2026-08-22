<?php

namespace App\Http\Controllers\Api\Parametrage;

use App\Http\Controllers\Controller;
use App\Http\Requests\Parametrage\Specialite\StoreSpecialiteRequest;
use App\Http\Requests\Parametrage\Specialite\UpdateSpecialiteRequest;
use App\Http\Requests\Parametrage\Specialite\UpdateSpecialiteStatusRequest;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Http\Resources\Parametrage\SpecialiteResource;
use App\Services\Parametrage\SpecialiteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;

class SpecialiteController extends Controller
{
    public function __construct(
        protected SpecialiteService $specialiteService) {
    }

    public function store(StoreSpecialiteRequest $request): JsonResponse
    {
        $specialite = $this->specialiteService->create(
            $request->validated()
        );

        Log::info('Création spécialité', [
            'action' => 'CREATE_SPECIALITE',
            'user_id' => auth()->id(),
            'specialite_id' => $specialite->id,
            'code' => $specialite->code,
            'libelle' => $specialite->libelle,
            'ip' => $request->ip(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Spécialité créée avec succès.',
            'data' => new SpecialiteResource($specialite),
        ], 201);
    }

    public function index(Request $request): JsonResponse
    {
    $specialites = $this->specialiteService->getAll(
        $request->only([
            'search',
            'est_actif',
            'sort_by',
            'sort_direction',
        ])
    );

    return response()->json([
        'success' => true,
        'message' => 'Liste des spécialités récupérée avec succès.',
        'data' => SpecialiteResource::collection($specialites),
        'pagination' => [
            'current_page' => $specialites->currentPage(),
            'last_page' => $specialites->lastPage(),
            'per_page' => $specialites->perPage(),
            'total' => $specialites->total(),
        ],
    ]);
}

public function update(UpdateSpecialiteRequest $request, int $id): JsonResponse
{
    try {
        $specialite = $this->specialiteService->findById($id);

        $anciennesValeurs = $specialite->only([
            'code',
            'libelle',
            'est_actif',
        ]);

        $specialite = $this->specialiteService->update(
            $id,
            $request->validated()
        );

        $nouvellesValeurs = $specialite->only([
            'code',
            'libelle',
            'est_actif',
        ]);

        Log::info('Modification spécialité', [
            'action' => 'UPDATE_SPECIALITE',
            'user_id' => auth()->id(),
            'specialite_id' => $specialite->id,
            'anciennes_valeurs' => $anciennesValeurs,
            'nouvelles_valeurs' => $nouvellesValeurs,
            'ip' => $request->ip(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Spécialité mise à jour avec succès.',
            'data' => new SpecialiteResource($specialite),
        ]);

    } catch (ModelNotFoundException $e) {
        return response()->json([
            'success' => false,
            'message' => 'Spécialité introuvable.',
        ], 404);
    }
}
public function changeStatus( UpdateSpecialiteStatusRequest $request, int $id): JsonResponse {
    try {
        $specialite = $this->specialiteService->findById($id);

        $ancienStatut = $specialite->est_actif;

        $specialite = $this->specialiteService->changeStatus(
            $id,
            $request->boolean('est_actif')
        );

        Log::info('Changement statut spécialité', [
            'action' => $specialite->est_actif
                ? 'ACTIVATE_SPECIALITE'
                : 'DEACTIVATE_SPECIALITE',

            'user_id' => auth()->id(),
            'specialite_id' => $specialite->id,
            'ancien_statut' => $ancienStatut,
            'nouveau_statut' => $specialite->est_actif,
            'ip' => $request->ip(),
        ]);

        return response()->json([
            'success' => true,
            'message' => $specialite->est_actif
                ? 'Spécialité activée avec succès.'
                : 'Spécialité désactivée avec succès.',
            'data' => new SpecialiteResource($specialite),
        ]);

    } catch (ModelNotFoundException $e) {
        return response()->json([
            'success' => false,
            'message' => 'Spécialité introuvable.',
        ], 404);
    }
}

public function actives(): JsonResponse
{
    $specialites = $this->specialiteService->getActives();

    return response()->json([
        'success' => true,
        'message' => 'Liste des spécialités actives récupérée avec succès.',
        'data' => SpecialiteResource::collection($specialites),
    ]);
}

}