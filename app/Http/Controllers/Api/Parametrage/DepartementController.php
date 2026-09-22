<?php

namespace App\Http\Controllers\Api\Parametrage;

use App\Http\Controllers\Controller;
use App\Http\Requests\Parametrage\Departement\StoreDepartementRequest;
use App\Http\Requests\Parametrage\Departement\UpdateDepartementRequest;
use App\Http\Resources\Parametrage\DepartementResource;
use App\Services\Parametrage\DepartementService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DepartementController extends Controller
{
    public function __construct(
        private readonly DepartementService $service
    ) {
    }

    /*
    |--------------------------------------------------------------------------
    | LISTE
    |--------------------------------------------------------------------------
    */

    public function index(Request $request): JsonResponse
    {
        $departements = $this->service->getAll([
            'search' => $request->input('search'),
            'region_id' => $request->input('region_id'),
            'est_actif' => $request->input('est_actif'),
            'sort_by' => $request->input('sort_by'),
            'sort_direction' => $request->input('sort_direction'),
            'per_page' => $request->input('per_page', 15),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Liste des départements récupérée avec succès.',
            'data' => [
                'data' => DepartementResource::collection(
                    $departements->items()
                ),

                'current_page' => $departements->currentPage(),
                'last_page' => $departements->lastPage(),
                'per_page' => $departements->perPage(),
                'total' => $departements->total(),
            ],
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | CRÉATION
    |--------------------------------------------------------------------------
    */

    public function store(
        StoreDepartementRequest $request
    ): JsonResponse {

        $departement = $this->service->create(
            $request->validated()
        );

        return response()->json([
            'success' => true,
            'message' => 'Département créé avec succès.',
            'data' => new DepartementResource($departement),
        ], 201);
    }


    /*
    |--------------------------------------------------------------------------
    | DÉTAIL
    |--------------------------------------------------------------------------
    */

    public function show(int $id): JsonResponse
    {
        try {

            $departement =
                $this->service->findById($id);

            return response()->json([
                'success' => true,
                'message' => 'Département récupéré avec succès.',
                'data' => new DepartementResource($departement),
            ]);

        } catch (ModelNotFoundException) {

            return response()->json([
                'success' => false,
                'message' => 'Département introuvable.',
            ], 404);
        }
    }


    /*
    |--------------------------------------------------------------------------
    | MODIFICATION
    |--------------------------------------------------------------------------
    */

    public function update(
        UpdateDepartementRequest $request,
        int $id
    ): JsonResponse {

        try {

            $departement = $this->service->update(
                $id,
                $request->validated()
            );

            return response()->json([
                'success' => true,
                'message' => 'Département modifié avec succès.',
                'data' => new DepartementResource($departement),
            ]);

        } catch (ModelNotFoundException) {

            return response()->json([
                'success' => false,
                'message' => 'Département introuvable.',
            ], 404);
        }
    }


    /*
    |--------------------------------------------------------------------------
    | CHANGEMENT DE STATUT
    |--------------------------------------------------------------------------
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
        ]);

        try {

            $departement =
                $this->service->changeStatut(
                    $id,
                    (bool) $validated['est_actif']
                );

            return response()->json([
                'success' => true,
                'message' => $departement->est_actif
                    ? 'Département activé avec succès.'
                    : 'Département désactivé avec succès.',
                'data' => new DepartementResource($departement),
            ]);

        } catch (ModelNotFoundException) {

            return response()->json([
                'success' => false,
                'message' => 'Département introuvable.',
            ], 404);
        }
    }


    /*
    |--------------------------------------------------------------------------
    | SUPPRESSION
    |--------------------------------------------------------------------------
    */

    public function destroy(int $id): JsonResponse
    {
        try {

            $this->service->delete($id);

            return response()->json([
                'success' => true,
                'message' => 'Département supprimé avec succès.',
            ]);

        } catch (ModelNotFoundException) {

            return response()->json([
                'success' => false,
                'message' => 'Département introuvable.',
            ], 404);

        } catch (\DomainException $exception) {

            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
            ], 422);
        }
    }
}