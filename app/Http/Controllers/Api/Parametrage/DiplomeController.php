<?php

namespace App\Http\Controllers\Api\Parametrage;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDiplomeRequest;
use App\Http\Requests\UpdateDiplomeRequest;
use App\Http\Resources\DiplomeResource;
use App\Models\Parametrage\Diplome;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DiplomeController extends Controller
{
    /** Liste des diplômes, avec recherche et pagination. */
    public function index(Request $request): JsonResponse
    {
        $query = Diplome::query();

        if ($search = $request->query('search')) {
            $query->where(function ($query) use ($search) {
                $query->where('code', 'like', "%{$search}%")
                    ->orWhere('libelle', 'like', "%{$search}%");
            });
        }

        if ($type = $request->query('type')) {
            $query->where('type', $type);
        }

        $perPage = (int) $request->query('per_page', 15);
        $diplomes = $query->orderBy('libelle')->paginate($perPage);

        return response()->json([
            'message' => 'Liste des diplômes',
            'data' => DiplomeResource::collection($diplomes),
            'meta' => [
                'current_page' => $diplomes->currentPage(),
                'last_page' => $diplomes->lastPage(),
                'per_page' => $diplomes->perPage(),
                'total' => $diplomes->total(),
            ],
        ]);
    }

    public function store(StoreDiplomeRequest $request): JsonResponse
    {
        $diplome = Diplome::create($request->validated());

        return response()->json([
            'message' => 'Diplôme créé avec succès',
            'data' => new DiplomeResource($diplome),
        ], 201);
    }

    public function show(Diplome $diplome): JsonResponse
    {
        return response()->json([
            'message' => 'Détail du diplôme',
            'data' => new DiplomeResource($diplome),
        ]);
    }

    public function update(UpdateDiplomeRequest $request, Diplome $diplome): JsonResponse
    {
        $diplome->update($request->validated());

        return response()->json([
            'message' => 'Diplôme mis à jour avec succès',
            'data' => new DiplomeResource($diplome),
        ]);
    }

    public function destroy(Diplome $diplome): JsonResponse
    {
        $diplome->delete();

        return response()->json([
            'message' => 'Diplôme supprimé avec succès',
        ]);
    }
}
