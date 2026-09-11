<?php

namespace App\Http\Controllers\Api\Parametrage;

use App\Http\Controllers\Controller;
use App\Models\Parametrage\SpecialiteEnseignant;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SpecialiteEnseignantController extends Controller
{
    public function index(Request $request)
    {
        $data = $request->validate([
            'search' => ['nullable', 'string', 'max:100'], 'statut' => ['nullable', Rule::in(['actif', 'inactif'])],
            'sort' => ['nullable', Rule::in(['code', 'libelle', 'description', 'statut', 'created_at'])],
            'direction' => ['nullable', Rule::in(['asc', 'desc'])], 'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);
        $sort = $data['sort'] ?? 'created_at';
        $direction = $data['direction'] ?? 'desc';
        $items = SpecialiteEnseignant::query()
            ->when(! empty($data['search']), function ($query) use ($data): void {
                $term = trim($data['search']);
                $query->where(fn ($query) => $query->where('code', 'ilike', "%{$term}%")->orWhere('libelle', 'ilike', "%{$term}%"));
            })
            ->when(! empty($data['statut']), fn ($query) => $query->where('statut', $data['statut']))
            ->orderBy($sort, $direction)->orderByDesc('id')->paginate($data['per_page'] ?? 10)->withQueryString();

        return response()->json(['success' => true, 'message' => 'Liste des spécialités.', 'data' => $items]);
    }

    public function store(Request $request)
    {
        $item = SpecialiteEnseignant::create($this->validated($request));

        return response()->json(['success' => true, 'message' => 'Spécialité créée avec succès.', 'data' => $item], 201);
    }

    public function show(SpecialiteEnseignant $discipline)
    {
        return response()->json(['success' => true, 'data' => $discipline]);
    }

    public function update(Request $request, SpecialiteEnseignant $discipline)
    {
        $discipline->update($this->validated($request, $discipline));

        return response()->json(['success' => true, 'message' => 'Spécialité modifiée avec succès.', 'data' => $discipline->refresh()]);
    }

    public function updateStatus(Request $request, SpecialiteEnseignant $discipline)
    {
        $data = $request->validate(['statut' => ['required', Rule::in(['actif', 'inactif'])]]);
        $discipline->update($data);

        return response()->json(['success' => true, 'message' => 'Statut de la spécialité modifié avec succès.', 'data' => $discipline->refresh()]);
    }

    public function destroy(SpecialiteEnseignant $discipline)
    {
        try {
            $discipline->delete();
        } catch (QueryException) {
            return response()->json(['success' => false, 'message' => 'Cette spécialité est utilisée et ne peut pas être supprimée.'], 409);
        }

        return response()->json(['success' => true, 'message' => 'Spécialité supprimée avec succès.']);
    }

    private function validated(Request $request, ?SpecialiteEnseignant $discipline = null): array
    {
        $request->mergeIfMissing(['statut' => $discipline?->statut ?? 'actif']);
        $request->merge(['code' => strtoupper(trim((string) ($request->input('code') ?: ($discipline?->code ?? ('SP'.bin2hex(random_bytes(9))))))), 'libelle' => trim((string) $request->input('libelle'))]);

        return $request->validate([
            'code' => ['required', 'string', 'max:20', 'regex:/^[A-Z0-9]+(?:[-_][A-Z0-9]+)*$/', Rule::unique('disciplines', 'code')->ignore($discipline?->id)],
            'libelle' => ['required', 'string', 'max:150'], 'description' => ['nullable', 'string', 'max:500'],
            'statut' => ['required', Rule::in(['actif', 'inactif'])],
        ]);
    }
}
