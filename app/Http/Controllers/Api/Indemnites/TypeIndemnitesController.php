<?php

namespace App\Http\Controllers\Api\Indemnites;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\Indemnites\Concerns\ApiResponseTrait;
use App\Http\Requests\Indemnites\StoreTypeIndemniteRequest;
use App\Http\Requests\Indemnites\UpdateTypeIndemniteRequest;
use App\Models\type_indemnites;
use Illuminate\Http\Request;

class TypeIndemnitesController extends Controller
{
    use ApiResponseTrait;

    public function index(Request $request)
    {
        $query = type_indemnites::query();

        if ($request->filled('actif')) {
            $query->where('actif', $request->boolean('actif'));
        }

        $types = $query->orderBy('libelle')->paginate($request->integer('per_page', 15));

        return $this->success('Liste des types d\'indemnités.', $types);
    }

    public function store(StoreTypeIndemniteRequest $request)
    {
        $type = type_indemnites::create($request->validated());

        return $this->success('Type d\'indemnité créé avec succès.', $type, 201);
    }

    public function show(string $id)
    {
        $type = type_indemnites::find($id);

        if (! $type) {
            return $this->error('Type d\'indemnité introuvable.', 404);
        }

        return $this->success('Type d\'indemnité trouvé.', $type);
    }

    public function update(UpdateTypeIndemniteRequest $request, string $id)
    {
        $type = type_indemnites::find($id);

        if (! $type) {
            return $this->error('Type d\'indemnité introuvable.', 404);
        }

        $type->update($request->validated());

        return $this->success('Type d\'indemnité mis à jour avec succès.', $type);
    }

    public function destroy(string $id)
    {
        $type = type_indemnites::find($id);

        if (! $type) {
            return $this->error('Type d\'indemnité introuvable.', 404);
        }

        if ($type->indemnites()->exists()) {
            return $this->error('Impossible de supprimer un type déjà utilisé par des indemnités.', 422);
        }

        $type->delete();

        return $this->success('Type d\'indemnité supprimé avec succès.');
    }
}
