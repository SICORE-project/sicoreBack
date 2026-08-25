<?php

namespace App\Http\Controllers\Api\Parametrage;

use App\Http\Controllers\Controller;
use App\Http\Requests\Parametrage\StoreRubriquePaieRequest;
use App\Http\Requests\Parametrage\UpdateRubriquePaieRequest;
use App\Models\Paie\RubriquePaie;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RubriquePaieController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'type' => ['nullable', Rule::in(['gain', 'retenue'])],
            'periodicite' => ['nullable', Rule::in(['mensuelle', 'ponctuelle', 'annuelle'])],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $query = RubriquePaie::query()
            ->when($filters['search'] ?? null, function ($query, string $search): void {
                $term = '%'.mb_strtolower($search).'%';
                $query->where(function ($query) use ($term): void {
                    $query->whereRaw('LOWER(code) LIKE ?', [$term])
                        ->orWhereRaw('LOWER(libelle) LIKE ?', [$term]);
                });
            })
            ->when($filters['type'] ?? null, fn ($query, string $type) => $query->where('type', $type))
            ->when($filters['periodicite'] ?? null, fn ($query, string $periodicite) => $query->where('periodicite', $periodicite))
            ->orderByDesc('id');

        $rubriques = $query->paginate((int) ($filters['per_page'] ?? 10))->withQueryString();

        return response()->json([
            'data' => $rubriques,
            'statistics' => [
                'total' => RubriquePaie::query()->count(),
                'gains' => RubriquePaie::query()->where('type', 'gain')->count(),
                'retenues' => RubriquePaie::query()->where('type', 'retenue')->count(),
            ],
        ]);
    }

    public function store(StoreRubriquePaieRequest $request): JsonResponse
    {
        $rubrique = RubriquePaie::query()->create($request->validated());

        return response()->json([
            'message' => 'Rubrique de paie ajoutée avec succès.',
            'data' => $rubrique,
        ], 201);
    }

    public function show(int $id): JsonResponse
    {
        return response()->json([
            'data' => RubriquePaie::query()->findOrFail($id),
        ]);
    }

    public function update(UpdateRubriquePaieRequest $request, int $id): JsonResponse
    {
        $rubrique = RubriquePaie::query()->findOrFail($id);
        $rubrique->update($request->validated());

        return response()->json([
            'message' => 'Rubrique de paie modifiée avec succès.',
            'data' => $rubrique->refresh(),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $rubrique = RubriquePaie::query()->findOrFail($id);

        if ($rubrique->corps()->exists()) {
            return response()->json([
                'message' => 'Cette rubrique est utilisée par un ou plusieurs corps et ne peut pas être supprimée.',
            ], 409);
        }

        try {
            $rubrique->delete();
        } catch (QueryException) {
            return response()->json([
                'message' => 'Cette rubrique est déjà utilisée et ne peut pas être supprimée.',
            ], 409);
        }

        return response()->json([
            'message' => 'Rubrique de paie supprimée avec succès.',
        ]);
    }
}
