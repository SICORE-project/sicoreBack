<?php

namespace App\Http\Controllers\Api\Admin\Personnel;

use App\Http\Controllers\Controller;
use App\Http\Requests\Administration\Personnel\StoreEnseignantRequest;
use App\Http\Requests\Administration\Personnel\UpdateEnseignantRequest;
use App\Http\Resources\Personnel\EnseignantResource;
use App\Services\Administration\Personnel\EnseignantService;
use Illuminate\Http\Request;

class EnseignantController extends Controller
{
    public function __construct(
        private EnseignantService $enseignantService
    ) {
    }

    /**
     * Liste des enseignants.
     */
    public function index(Request $request)
    {
        $perPage = (int) $request->input('per_page', 20);

        $perPage = min(max($perPage, 1), 100);

        $enseignants = $this->enseignantService
            ->paginate($perPage);

        return EnseignantResource::collection($enseignants)
            ->additional([
                'success' => true,
                'message' => 'Liste des enseignants récupérée avec succès.',
            ]);
    }

    /**
     * Enregistrer un nouvel enseignant.
     */
    public function store(StoreEnseignantRequest $request)
    {
        $enseignant = $this->enseignantService->create(
            $request->validated(),
            $request->user()?->id
        );

        return response()->json([
            'success' => true,
            'message' => 'Enseignant enregistré avec succès.',
            'data' => new EnseignantResource($enseignant),
        ], 201);
    }

    /**
     * Afficher un enseignant.
     */
    public function show(int $id)
    {
        $enseignant = $this->enseignantService->find($id);

        return response()->json([
            'success' => true,
            'message' => 'Dossier enseignant récupéré avec succès.',
            'data' => new EnseignantResource($enseignant),
        ]);
    }

    public function update(UpdateEnseignantRequest $request, int $id)
    {
        $enseignant = $this->enseignantService->update(
            $this->enseignantService->find($id),
            $request->validated(),
            $request->user()?->id
        );

        return response()->json([
            'success' => true,
            'message' => 'Enseignant modifié avec succès.',
            'data' => new EnseignantResource($enseignant),
        ]);
    }

    public function destroy(int $id)
    {
        $this->enseignantService->delete($this->enseignantService->find($id));

        return response()->json([
            'success' => true,
            'message' => 'Enseignant supprimé avec succès.',
        ]);
    }
}