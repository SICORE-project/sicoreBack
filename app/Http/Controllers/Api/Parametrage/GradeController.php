<?php

namespace App\Http\Controllers\Api\Parametrage;

use App\Http\Controllers\Controller;
use App\Models\Parametrage\Grade;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class GradeController extends Controller
{
    public function index(Request $request)
    {
        $grades = Grade::query()
            ->when($request->filled('search'), function ($query) use ($request): void {
                $search = trim((string) $request->input('search'));
                $query->where(function ($query) use ($search): void {
                    $query->where('libelle', 'ilike', "%{$search}%")
                        ->orWhere('description', 'ilike', "%{$search}%");
                });
            })
            ->orderBy('libelle')
            ->paginate(min(100, max(1, $request->integer('per_page', 10))));

        return response()->json(['message' => 'Liste des grades récupérée avec succès.', 'data' => $grades]);
    }

    public function store(Request $request)
    {
        $request->merge(['libelle' => trim((string) $request->input('libelle'))]);
        $data = $request->validate([
            'libelle' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);
        $data['code'] = $this->uniqueCode($data['libelle']);
        $data['est_actif'] = true;

        return response()->json(['message' => 'Grade créé avec succès.', 'data' => Grade::create($data)], 201);
    }

    public function show(int $id)
    {
        $grade = Grade::query()->find($id);
        return $grade
            ? response()->json(['message' => 'Grade récupéré avec succès.', 'data' => $grade])
            : response()->json(['message' => 'Grade introuvable.'], 404);
    }

    public function update(Request $request, int $id)
    {
        $grade = Grade::query()->find($id);
        if (! $grade) {
            return response()->json(['message' => 'Grade introuvable.'], 404);
        }
        $request->merge(['libelle' => trim((string) $request->input('libelle'))]);
        $data = $request->validate([
            'libelle' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);
        $grade->update($data);

        return response()->json(['message' => 'Grade modifié avec succès.', 'data' => $grade->fresh()]);
    }

    public function destroy(int $id)
    {
        $grade = Grade::query()->find($id);
        if (! $grade) {
            return response()->json(['message' => 'Grade introuvable.'], 404);
        }
        if ($grade->enseignants()->exists()) {
            return response()->json(['message' => 'Ce grade est associé à des enseignants et ne peut pas être supprimé.'], 409);
        }
        try {
            $grade->delete();
        } catch (QueryException) {
            return response()->json(['message' => 'Ce grade est utilisé par d’autres données et ne peut pas être supprimé.'], 409);
        }

        return response()->json(['message' => 'Grade supprimé avec succès.']);
    }

    private function uniqueCode(string $libelle): string
    {
        $base = Str::limit(Str::upper(Str::slug($libelle, '-')) ?: 'GRADE', 24, '');
        $code = $base;
        $suffix = 2;
        while (Grade::query()->where('code', $code)->exists()) {
            $code = Str::limit($base, 24, '').'-'.$suffix++;
        }

        return $code;
    }
}
