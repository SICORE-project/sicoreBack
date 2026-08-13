<?php

namespace App\Http\Controllers\Api\Indemnites;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\Indemnites\Concerns\ApiResponseTrait;
use App\Http\Requests\Indemnites\StoreConvocationRequest;
use App\Http\Requests\Indemnites\UpdateConvocationRequest;
use App\Models\Indemnite\Convocations as ConvocationModel;
use Illuminate\Http\Request;

class ConvocationsController extends Controller
{
    use ApiResponseTrait;

    public function index(Request $request)
    {
        // Eager loading nécessaire à la liste DAGE (point 3 du cahier des
        // charges "Transmission des convocations") : Agent / Type / Session /
        // Centre / Rôle / Lieu de service proviennent tous des relations,
        // pas seulement des colonnes de `convocations`.
        $query = ConvocationModel::withCount('enseignants')
            ->with(['typeConvocation', 'centres', 'enseignants.lieuService']);

        if ($request->filled('statut')) {
            $query->where('statut', $request->query('statut'));
        }

        if ($request->filled('utilisateur_id')) {
            $query->where('utilisateur_id', $request->query('utilisateur_id'));
        }

        if ($request->filled('objet')) {
            $query->where('objet', 'like', '%'.$request->query('objet').'%');
        }

        if ($request->filled('date')) {
            $query->whereDate('date_emission', $request->query('date'));
        }

        
        if ($request->filled('metier')) {
            $metier = $request->query('metier');
            $query->whereHas('centres', function ($q) use ($metier) {
                $q->where('metier', 'like', '%'.$metier.'%');
            });
        }

        if ($request->filled('centre')) {
            $centre = $request->query('centre');
            $query->whereHas('centres', function ($q) use ($centre) {
                $q->where('centre', 'like', '%'.$centre.'%');
            });
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
     *
     * L'import en masse (option A du workflow DAGE : fichier DECPC ->
     * enregistrements) est géré séparément par ConvocationImportController.
     */
    public function store(StoreConvocationRequest $request)
    {
        $convocation = ConvocationModel::create($request->validated());

        return $this->success('Convocation créée avec succès.', $convocation, 201);
    }

    public function show(string $id)
    {
        $convocation = ConvocationModel::with([
            'envois',
            'centres.chefCentre',
            'centres.presidentJury',
            'centres.metiers',
            'centres.enseignants.lieuService',
            'enseignants.lieuService',
            'typeConvocation',
        ])->find($id);

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
