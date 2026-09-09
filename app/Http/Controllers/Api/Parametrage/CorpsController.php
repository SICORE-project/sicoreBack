<?php

namespace App\Http\Controllers\Api\Parametrage;

use App\Http\Controllers\Controller;
use App\Models\Parametrage\CorpsEnseignant;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CorpsController extends Controller
{
    /**
     * Liste des corps enseignants
     */
    public function index(Request $request)
    {
        $corps = CorpsEnseignant::query()
            ->when($request->filled('search'), function ($query) use ($request): void {
                $search = trim((string) $request->input('search'));
                $query->where(function ($query) use ($search): void {
                    $query->where('libelle', 'ilike', "%{$search}%")
                        ->orWhere('description', 'ilike', "%{$search}%");
                });
            })
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(min(100, max(1, $request->integer('per_page', 10))));

        return response()->json([
            'message' => 'Liste des corps enseignants récupérée avec succès.',
            'data' => $corps,
        ], 200);
    }

    /**
     * Création d'un corps enseignant
     */
    public function store(Request $request)
    {
        $request->merge(['libelle' => trim((string) $request->input('libelle'))]);

        $data = $request->validate([
            'libelle' => [
                'required',
                'string',
                'max:255',
            ],

            

            'description' => [
                'nullable',
                'string',
            ],
        ]);

        $data['code'] = $this->uniqueCode($data['libelle']);
        $corps = CorpsEnseignant::create($data);

        return response()->json([
            'message' => 'Corps enseignant créé avec succès.',
            'data' => $corps,
        ], 201);
    }

    /**
     * Afficher un corps enseignant
     */
    public function show($id)
    {
        $corps = CorpsEnseignant::find($id);

        if (!$corps) {
            return response()->json([
                'message' => 'Corps enseignant introuvable.',
            ], 404);
        }

        return response()->json([
            'message' => 'Corps enseignant récupéré avec succès.',
            'data' => $corps,
        ], 200);
    }

    /**
     * Modifier un corps enseignant
     */
    public function update(Request $request, $id)
    {
        $corps = CorpsEnseignant::find($id);

        if (!$corps) {
            return response()->json([
                'message' => 'Corps enseignant introuvable.',
            ], 404);
        }

        $request->merge(['libelle' => trim((string) $request->input('libelle'))]);

        $data = $request->validate([
            'libelle' => [
                'required',
                'string',
                'max:255',
            ],

           

            'description' => [
                'nullable',
                'string',
            ],
        ]);

        $corps->update($data);

        return response()->json([
            'message' => 'Corps enseignant modifié avec succès.',
            'data' => $corps->fresh(),
        ], 200);
    }

    /**
     * Suppression d'un corps enseignant
     */
    public function destroy($id)
    {
        $corps = CorpsEnseignant::query()->find($id);

        if (!$corps) {
            return response()->json([
                'message' => 'Corps enseignant introuvable.',
            ], 404);
        }

        $dependances = array_values(array_filter([
            $corps->categories()->exists() ? 'des catégories' : null,
            $corps->enseignants()->exists() ? 'des enseignants' : null,
            $corps->rubriques()->exists() ? 'des rubriques de paie' : null,
        ]));

        if ($dependances !== []) {
            return response()->json([
                'message' => 'Ce corps enseignant est associé à '.implode(', ', $dependances).' et ne peut pas être supprimé.',
                'dependencies' => $dependances,
            ], 409);
        }

        try {
            $corps->delete();
        } catch (QueryException) {
            return response()->json([
                'message' => 'Ce corps enseignant est utilisé par d’autres données et ne peut pas être supprimé.',
            ], 409);
        }

        return response()->json([
            'message' => 'Corps enseignant supprimé avec succès.',
        ], 200);
    }

    private function uniqueCode(string $libelle): string
    {
        $base = Str::limit(Str::upper(Str::slug($libelle, '-')) ?: 'CORPS', 44, '');
        $code = $base;
        $suffix = 2;

        while (CorpsEnseignant::query()->where('code', $code)->exists()) {
            $code = Str::limit($base, 44, '').'-'.$suffix++;
        }

        return $code;
    }
}
