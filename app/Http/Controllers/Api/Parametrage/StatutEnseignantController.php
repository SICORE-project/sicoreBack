<?php

namespace App\Http\Controllers\Api\Parametrage;

use App\Http\Controllers\Controller;
use App\Models\Parametrage\StatutEnseignant;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StatutEnseignantController extends Controller
{
    /**
     * Liste des statut enseignants
     */
    public function index(Request $request)
    {
        $corps = StatutEnseignant::query()
            ->when($request->filled('search'), function ($query) use ($request): void {
                $search = trim((string) $request->input('search'));
                $query->where(function ($query) use ($search): void {
                    $query->whereRaw('LOWER(libelle) LIKE ?', ['%'.mb_strtolower($search).'%'])
                        ->orWhereRaw('LOWER(description) LIKE ?', ['%'.mb_strtolower($search).'%']);
                });
            })
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(min(100, max(1, $request->integer('per_page', 10))));

        return response()->json([
            'message' => 'Liste des statut enseignants récupérée avec succès.',
            'data' => $corps,
        ], 200);
    }

    /**
     * Création d'un statut enseignant
     */
    public function store(Request $request)
    {
        $request->merge(['libelle' => trim((string) $request->input('libelle'))]);

        $data = $request->validate([
            'libelle' => [
                'required',
                'string',
                'max:50',
                Rule::unique('statuts_enseignant', 'libelle'),
            ],

            

            'description' => [
                'nullable',
                'string',
            ],
        ], ['libelle.unique' => 'Un statut enseignant avec ce libellé existe déjà.']);

        $data['code'] = $this->uniqueCode($data['libelle']);
        $corps = StatutEnseignant::create($data);

        return response()->json([
            'message' => 'Corps enseignant créé avec succès.',
            'data' => $corps,
        ], 201);
    }

    /**
     * Afficher un statut enseignant
     */
    public function show($id)
    {
        $corps = StatutEnseignant::find($id);

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
     * Modifier un statut enseignant
     */
    public function update(Request $request, $id)
    {
        $corps = StatutEnseignant::find($id);

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
                'max:50',
                Rule::unique('statuts_enseignant', 'libelle')->ignore($corps->id),
            ],

           

            'description' => [
                'nullable',
                'string',
            ],
        ], ['libelle.unique' => 'Un statut enseignant avec ce libellé existe déjà.']);

        $corps->update($data);

        return response()->json([
            'message' => 'Corps enseignant modifié avec succès.',
            'data' => $corps->fresh(),
        ], 200);
    }

    /**
     * Suppression d'un statut enseignant
     */
    public function destroy($id)
    {
        $corps = StatutEnseignant::query()->find($id);

        if (!$corps) {
            return response()->json([
                'message' => 'Corps enseignant introuvable.',
            ], 404);
        }

        if ($corps->enseignants()->exists()) {
            return response()->json(['message' => 'Ce statut est utilisé par des enseignants et ne peut pas être supprimé.'], 409);
        }

        try {
            $corps->delete();
        } catch (QueryException) {
            return response()->json([
                'message' => 'Ce statut enseignant est utilisé par d’autres données et ne peut pas être supprimé.',
            ], 409);
        }

        return response()->json([
            'message' => 'Corps enseignant supprimé avec succès.',
        ], 200);
    }

    private function uniqueCode(string $libelle): string
    {
        $base = Str::limit(Str::upper(Str::slug($libelle, '-')) ?: 'CORPS', 16, '');
        $code = $base;
        $suffix = 2;

        while (StatutEnseignant::query()->where('code', $code)->exists()) {
            $code = Str::limit($base, 16, '').'-'.$suffix++;
        }

        return $code;
    }
}
