<?php

namespace App\Http\Controllers\Api\Parametrage;

use App\Http\Controllers\Controller;
use App\Http\Requests\Parametrage\ChangeStatutLieuServiceRequest;
use App\Http\Requests\Parametrage\IndexLieuServiceRequest;
use App\Http\Requests\Parametrage\StoreLieuServiceRequest;
use App\Http\Requests\Parametrage\UpdateLieuServiceRequest;
use App\Http\Resources\Parametrage\LieuServiceResource;
use App\Models\Parametrage\LieuService;
use App\Services\Parametrage\LieuServiceScope;
use Illuminate\Http\JsonResponse;

class LieuServiceController extends Controller
{
    public function index(IndexLieuServiceRequest $request, LieuServiceScope $scope): JsonResponse
    {
        $validated = $request->validated();

        $query = $scope->apply(LieuService::query(), $request->user())
            ->with([
                'ia:id,code,libelle',
                'ief:id,code,libelle,ia_id',
            ]);

        if (! empty($validated['search'])) {
            $term = $validated['search'];
            $query->where(fn ($query) => $query
                ->where('code', 'like', "%{$term}%")
                ->orWhere('libelle', 'like', "%{$term}%"));
        }

        foreach (['ia_id', 'ief_id', 'type'] as $filter) {
            if (array_key_exists($filter, $validated)) {
                $query->where($filter, $validated[$filter]);
            }
        }

        if (array_key_exists('est_actif', $validated)) {
            $query->where('est_actif', $request->boolean('est_actif'));
        }

        $lieux = $query
            ->orderBy($validated['sort'] ?? 'libelle', $validated['direction'] ?? 'asc')
            ->paginate($validated['per_page'] ?? 15)
            ->withQueryString();

        return response()->json([
            'success' => true,
            'message' => 'Liste des lieux de service récupérée avec succès.',
            'data' => LieuServiceResource::collection($lieux->getCollection()),
            'meta' => [
                'current_page' => $lieux->currentPage(),
                'last_page' => $lieux->lastPage(),
                'per_page' => $lieux->perPage(),
                'total' => $lieux->total(),
            ],
        ]);
    }

    public function store(StoreLieuServiceRequest $request): JsonResponse
    {
        $lieuService = LieuService::create([
            ...$request->validated(),
            'est_actif' => true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Lieu de service créé avec succès.',
            'data' => new LieuServiceResource($lieuService->load(['ia', 'ief'])),
        ], 201);
    }

    public function update(
        UpdateLieuServiceRequest $request,
        LieuService $lieuService
    ): JsonResponse {
        $lieuService->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Lieu de service modifié avec succès.',
            'data' => $lieuService->refresh()->load(['ia', 'ief'])->append('hierarchie_coherente'),
        ]);
    }

    public function updateStatut(
        ChangeStatutLieuServiceRequest $request,
        LieuService $lieuService
    ): JsonResponse {
        $lieuService->update([
            'est_actif' => $request->validated('est_actif'),
        ]);

        return response()->json([
            'success' => true,
            'message' => $lieuService->est_actif
                ? 'Lieu de service activé avec succès.'
                : 'Lieu de service désactivé avec succès.',
            'data' => $lieuService->refresh()->load(['ia', 'ief'])->append('hierarchie_coherente'),
        ]);
    }
}
