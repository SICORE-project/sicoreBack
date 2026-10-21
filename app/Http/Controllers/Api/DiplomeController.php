<?php

namespace App\Http\Controllers\Api;

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
        $request->validate([
            'salaire_min' => ['nullable', 'numeric', 'min:0'],
            'salaire_max' => array_merge(['nullable', 'numeric', 'min:0'], $request->filled('salaire_min') ? ['gte:salaire_min'] : []),
        ]);
        $query = Diplome::query();
        if ($request->filled('salaire_min')) {
            $query->where('salaire_brut', '>=', $request->input('salaire_min'));
        }
        if ($request->filled('salaire_max')) {
            $query->where('salaire_brut', '<=', $request->input('salaire_max'));
        }

        if ($search = $request->query('search')) {
            $query->where(function ($query) use ($search) {
                $query->where('libelle', 'like', "%{$search}%");
            });
        }

        if ($request->filled('libelle')) {
            $query->whereRaw('UPPER(TRIM(libelle)) = ?', [mb_strtoupper(trim((string) $request->query('libelle')), 'UTF-8')]);
        }
        if ($request->filled('categorie_id')) {
            $query->where('categorie_id', $request->integer('categorie_id'));
        }

        $perPage = min(100, max(1, $request->integer('per_page', 10)));
        $diplomes = $query->with('categorie')->orderBy('libelle')->paginate($perPage);

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
            'data' => new DiplomeResource($diplome->load('categorie')),
        ], 201);
    }

    public function show(Diplome $diplome): JsonResponse
    {
        return response()->json([
            'message' => 'Détail du diplôme',
            'data' => new DiplomeResource($diplome->load('categorie')),
        ]);
    }

    public function update(UpdateDiplomeRequest $request, Diplome $diplome): JsonResponse
    {
        $diplome->update($request->validated());

        return response()->json([
            'message' => 'Diplôme mis à jour avec succès',
            'data' => new DiplomeResource($diplome->load('categorie')),
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
