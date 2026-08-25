<?php

namespace App\Http\Controllers\Api\Parametrage;

use App\Http\Controllers\Controller;
use App\Http\Requests\Parametrage\StoreCategorieRequest;
use App\Http\Requests\Parametrage\UpdateCategorieRequest;
use App\Services\Parametrage\CategorieService;
use Illuminate\Http\Request;

class CategorieController extends Controller
{
    public function __construct(
        private CategorieService $categorieService
    ) {
    }

    public function index(Request $request)
    {
        return response()->json([
            'message' => 'Liste des catégories récupérée avec succès.',
            'data' => $this->categorieService->getAll($request->only(['search', 'corps_id', 'per_page'])),
        ]);
    }

    public function store(StoreCategorieRequest $request)
    {
        $categorie = $this->categorieService->create(
            $request->validated()
        );

        return response()->json([
            'message' => 'Catégorie créée avec succès.',
            'data' => $categorie->load('corps'),
        ], 201);
    }

    public function show($id)
    {
        $categorie = $this->categorieService->findById($id);

        if (!$categorie) {
            return response()->json([
                'message' => 'Catégorie introuvable.',
            ], 404);
        }

        return response()->json([
            'message' => 'Catégorie récupérée avec succès.',
            'data' => $categorie,
        ]);
    }

    public function update(
        UpdateCategorieRequest $request,
        $id
    ) {
        $categorie = $this->categorieService->findById($id);

        if (!$categorie) {
            return response()->json([
                'message' => 'Catégorie introuvable.',
            ], 404);
        }

        $categorie = $this->categorieService->update(
            $categorie,
            $request->validated()
        );

        return response()->json([
            'message' => 'Catégorie modifiée avec succès.',
            'data' => $categorie,
        ]);
    }

    public function destroy($id)
    {
        $categorie = $this->categorieService->findById($id);

        if (!$categorie) {
            return response()->json([
                'message' => 'Catégorie introuvable.',
            ], 404);
        }

        $this->categorieService->delete($categorie);

        return response()->json([
            'message' => 'Catégorie supprimée avec succès.',
        ]);
    }

}
