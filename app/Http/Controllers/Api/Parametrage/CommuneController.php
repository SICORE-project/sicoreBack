<?php

namespace App\Http\Controllers\Api\Parametrage;

use App\Http\Controllers\Controller;
use App\Http\Requests\Parametrage\Commune\StoreCommuneRequest;
use App\Http\Requests\Parametrage\Commune\UpdateCommuneRequest;
use App\Http\Resources\Parametrage\CommuneResource;
use App\Services\Parametrage\CommuneService;
use DomainException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CommuneController extends Controller
{
    public function __construct(
        private readonly CommuneService $communeService
    ) {
    }

    /**
     * Liste des communes.
     */
    public function index(Request $request): JsonResponse
    {
        $communes = $this->communeService->getAll(
            $request->only([
                'search',
                'region_id',
                'departement_id',
                'est_actif',
                'sort_by',
                'sort_direction',
                'per_page',
            ])
        );

        return response()->json([
            'success' => true,
            'message' => 'Liste des communes récupérée avec succès.',
            'data' => [
                'data' => CommuneResource::collection(
                    $communes->items()
                ),
                'current_page' => $communes->currentPage(),
                'last_page' => $communes->lastPage(),
                'per_page' => $communes->perPage(),
                'total' => $communes->total(),
            ],
        ]);
    }

    /**
     * Créer une commune.
     */
    public function store(
        StoreCommuneRequest $request
    ): JsonResponse {
        $commune = $this->communeService->create(
            $request->validated()
        );

        return response()->json([
            'success' => true,
            'message' => 'Commune créée avec succès.',
            'data' => new CommuneResource($commune),
        ], 201);
    }

    /**
     * Afficher une commune.
     */
    public function show(int $id): JsonResponse
    {
        try {
            $commune = $this->communeService->findById($id);

            return response()->json([
                'success' => true,
                'message' => 'Commune récupérée avec succès.',
                'data' => new CommuneResource($commune),
            ]);
        } catch (ModelNotFoundException) {
            return response()->json([
                'success' => false,
                'message' => 'Commune introuvable.',
            ], 404);
        }
    }

    /**
     * Modifier une commune.
     */
    public function update(
        UpdateCommuneRequest $request,
        int $id
    ): JsonResponse {
        try {
            $commune = $this->communeService->update(
                $id,
                $request->validated()
            );

            return response()->json([
                'success' => true,
                'message' => 'Commune modifiée avec succès.',
                'data' => new CommuneResource($commune),
            ]);
        } catch (ModelNotFoundException) {
            return response()->json([
                'success' => false,
                'message' => 'Commune introuvable.',
            ], 404);
        }
    }

    /**
     * Activer ou désactiver une commune.
     */
    public function changeStatut(
        Request $request,
        int $id
    ): JsonResponse {
        $validated = $request->validate([
            'est_actif' => [
                'required',
                'boolean',
            ],
        ], [
            'est_actif.required' =>
                'Le statut est obligatoire.',

            'est_actif.boolean' =>
                'Le statut doit être vrai ou faux.',
        ]);

        try {
            $commune = $this->communeService->changeStatut(
                $id,
                (bool) $validated['est_actif']
            );

            return response()->json([
                'success' => true,
                'message' => $commune->est_actif
                    ? 'Commune activée avec succès.'
                    : 'Commune désactivée avec succès.',
                'data' => new CommuneResource($commune),
            ]);
        } catch (ModelNotFoundException) {
            return response()->json([
                'success' => false,
                'message' => 'Commune introuvable.',
            ], 404);
        }
    }

    /**
     * Supprimer une commune.
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $this->communeService->delete($id);

            return response()->json([
                'success' => true,
                'message' => 'Commune supprimée avec succès.',
            ]);
        } catch (ModelNotFoundException) {
            return response()->json([
                'success' => false,
                'message' => 'Commune introuvable.',
            ], 404);
        } catch (DomainException $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
            ], 422);
        }
    }
}