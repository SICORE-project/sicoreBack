<?php

namespace App\Http\Controllers\Api\Parametrage;

use App\Http\Controllers\Controller;
use App\Http\Requests\Parametrage\StorePeriodePaieRequest;
use App\Http\Requests\Parametrage\UpdatePeriodePaieRequest;
use App\Models\Paie\PeriodePaie;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PeriodePaieController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $periodes = PeriodePaie::query()
            ->when($filters['search'] ?? null, function ($query, string $search): void {
                $term = '%'.mb_strtolower($search).'%';
                $query->where(function ($query) use ($term): void {
                    $query->whereRaw('LOWER(code) LIKE ?', [$term])
                        ->orWhereRaw('LOWER(libelle) LIKE ?', [$term]);
                });
            })
            ->orderByDesc('id')
            ->paginate((int) ($filters['per_page'] ?? 10))
            ->withQueryString();

        return response()->json(['data' => $periodes]);
    }

    public function store(StorePeriodePaieRequest $request): JsonResponse
    {
        $periode = PeriodePaie::query()->create($request->validated());

        return response()->json([
            'message' => 'Période de paie ajoutée avec succès.',
            'data' => $periode,
        ], 201);
    }

    public function show(int $id): JsonResponse
    {
        return response()->json([
            'data' => PeriodePaie::query()->findOrFail($id),
        ]);
    }

    public function update(UpdatePeriodePaieRequest $request, int $id): JsonResponse
    {
        $periode = PeriodePaie::query()->findOrFail($id);
        $periode->update($request->validated());

        return response()->json([
            'message' => 'Période de paie modifiée avec succès.',
            'data' => $periode->refresh(),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $periode = PeriodePaie::query()->findOrFail($id);

        try {
            $periode->delete();
        } catch (QueryException) {
            return response()->json([
                'message' => 'Cette période de paie est déjà utilisée et ne peut pas être supprimée.',
            ], 409);
        }

        return response()->json([
            'message' => 'Période de paie supprimée avec succès.',
        ]);
    }
}
