<?php

namespace App\Http\Controllers\Api\Parametrage;

use App\Http\Controllers\Controller;
use App\Http\Requests\Parametrage\ChangeStatutLieuServiceRequest;
use App\Http\Requests\Parametrage\UpdateLieuServiceRequest;
use App\Models\Parametrage\LieuService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LieuServiceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'ia_id' => ['nullable', 'integer', 'exists:ias,id'],
            'ief_id' => ['nullable', 'integer', 'exists:iefs,id'],
            'type' => ['nullable', 'string', 'max:50'],
            'est_actif' => ['nullable', 'boolean'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $query = LieuService::query()->with(['ia', 'ief']);

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

        $lieux = $query->orderBy('libelle')->paginate($validated['per_page'] ?? 15);
        $lieux->getCollection()->each->append('hierarchie_coherente');

        return response()->json([
            'success' => true,
            ...$lieux->toArray(),
        ]);
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
