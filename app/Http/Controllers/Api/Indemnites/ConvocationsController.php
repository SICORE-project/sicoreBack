<?php

namespace App\Http\Controllers\Api\Indemnites;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\Indemnites\Concerns\ApiResponseTrait;
use App\Http\Requests\Indemnites\StoreConvocationRequest;
use App\Http\Requests\Indemnites\UpdateConvocationRequest;
use App\Models\Convocations as ConvocationModel;
use Illuminate\Http\Request;

class ConvocationsController extends Controller
{
    use ApiResponseTrait;

    public function index(Request $request)
    {
        $query = ConvocationModel::query();

        if ($request->filled('statut')) {
            $query->where('statut', $request->query('statut'));
        }

        if ($request->filled('utilisateur_id')) {
            $query->where('utilisateur_id', $request->query('utilisateur_id'));
        }

        $convocations = $query->latest()->paginate($request->integer('per_page', 15));

        return $this->success('Liste des convocations.', $convocations);
    }

    /**
     * Créé une nouvelle convocation.
     *
     * NOTE: le dépôt de fichier a été déplacé vers ConvocationFichierController
     * (POST /convocations/{id}/fichier) — cette méthode ne gère plus que
     * la création, ce qui évite la bifurcation conditionnelle ambiguë
     * qui existait auparavant dans StoreConvocationRequest.
     */
    public function store(StoreConvocationRequest $request)
    {
        $convocation = ConvocationModel::create($request->validated());

        return $this->success('Convocation créée avec succès.', $convocation, 201);
    }

    public function show(string $id)
    {
        $convocation = ConvocationModel::with('envois')->find($id);

        if (! $convocation) {
            return $this->error('Convocation introuvable.', 404);
        }

        return $this->success('Convocation trouvée.', $convocation);
    }

    public function update(UpdateConvocationRequest $request, string $id)
    {
        $convocation = ConvocationModel::find($id);

        if (! $convocation) {
            return $this->error('Convocation introuvable.', 404);
        }

        $convocation->update($request->validated());

        return $this->success('Convocation mise à jour avec succès.', $convocation);
    }

    public function destroy(string $id)
    {
        $convocation = ConvocationModel::find($id);

        if (! $convocation) {
            return $this->error('Convocation introuvable.', 404);
        }

        $convocation->delete();

        return $this->success('Convocation supprimée avec succès.');
    }
}
