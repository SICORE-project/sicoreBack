<?php

namespace App\Http\Controllers\Api\Indemnites;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\Indemnites\Concerns\ApiResponseTrait;
use App\Http\Requests\Indemnites\StoreServiceFaitRequest;
use App\Http\Requests\Indemnites\UpdateServiceFaitRequest;
use App\Http\Requests\Indemnites\RejeterServiceFaitRequest;
use App\Http\Requests\Indemnites\CorrigerServiceFaitRequest;
use App\Models\ServiceFait;
use App\Models\ServiceFaitHistorique;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ServicesFaitsController extends Controller
{
    use ApiResponseTrait;

    public function index(Request $request)
    {
        $query = ServiceFait::query();

        if ($request->filled('statut')) {
            $query->where('statut', $request->query('statut'));
        }

        if ($request->filled('enseignant_id')) {
            $query->where('enseignant_id', $request->query('enseignant_id'));
        }

        $serviceFaits = $query->latest()->paginate($request->integer('per_page', 15));

        return $this->success('Liste des services faits.', $serviceFaits);
    }

    public function store(StoreServiceFaitRequest $request)
    {
        $data = $request->validated();
        $data['statut'] = 'en_attente';
        $data['nombre_jours'] = $data['nombre_jours']
            ?? (\Carbon\Carbon::parse($data['date_debut'])->diffInDays(\Carbon\Carbon::parse($data['date_fin'])) + 1);

        $serviceFait = DB::transaction(function () use ($data, $request) {
            $sf = ServiceFait::create($data);

            ServiceFaitHistorique::create([
                'service_fait_id' => $sf->id,
                'utilisateur_id' => $request->user()?->id,
                'action' => 'creation',
                'nouvelles_valeurs' => $sf->toArray(),
            ]);

            return $sf;
        });

        return $this->success('Service fait créé avec succès.', $serviceFait, 201);
    }

    public function show(string $id)
    {
        $serviceFait = ServiceFait::find($id);

        if (! $serviceFait) {
            return $this->error('Service fait introuvable.', 404);
        }

        return $this->success('Service fait trouvé.', $serviceFait);
    }

    public function update(UpdateServiceFaitRequest $request, string $id)
    {
        $serviceFait = ServiceFait::find($id);

        if (! $serviceFait) {
            return $this->error('Service fait introuvable.', 404);
        }

        $anciennesValeurs = $serviceFait->toArray();
        $serviceFait->update($request->validated());

        ServiceFaitHistorique::create([
            'service_fait_id' => $serviceFait->id,
            'utilisateur_id' => $request->user()?->id,
            'action' => 'modification',
            'anciennes_valeurs' => $anciennesValeurs,
            'nouvelles_valeurs' => $serviceFait->fresh()->toArray(),
        ]);

        return $this->success('Service fait mis à jour avec succès.', $serviceFait);
    }

    public function destroy(string $id)
    {
        $serviceFait = ServiceFait::find($id);

        if (! $serviceFait) {
            return $this->error('Service fait introuvable.', 404);
        }

        $serviceFait->delete();

        return $this->success('Service fait supprimé avec succès.');
    }

    public function valider(Request $request, string $id)
    {
        $serviceFait = ServiceFait::find($id);

        if (! $serviceFait) {
            return $this->error('Service fait introuvable.', 404);
        }

        $serviceFait->update([
            'statut' => 'valide',
            'valide_par' => $request->user()?->id,
            'valide_at' => now(),
        ]);

        ServiceFaitHistorique::create([
            'service_fait_id' => $serviceFait->id,
            'utilisateur_id' => $request->user()?->id,
            'action' => 'validation',
            'nouvelles_valeurs' => $serviceFait->fresh()->toArray(),
        ]);

        return $this->success('Service fait validé avec succès.', $serviceFait);
    }

    public function rejeter(RejeterServiceFaitRequest $request, string $id)
    {
        $serviceFait = ServiceFait::find($id);

        if (! $serviceFait) {
            return $this->error('Service fait introuvable.', 404);
        }

        $serviceFait->update([
            'statut' => 'rejete',
            'motif_rejet' => $request->validated('motif_rejet'),
        ]);

        ServiceFaitHistorique::create([
            'service_fait_id' => $serviceFait->id,
            'utilisateur_id' => $request->user()?->id,
            'action' => 'rejet',
            'nouvelles_valeurs' => $serviceFait->fresh()->toArray(),
        ]);

        return $this->success('Service fait rejeté.', $serviceFait);
    }

    public function corriger(CorrigerServiceFaitRequest $request, string $id)
    {
        $serviceFait = ServiceFait::find($id);

        if (! $serviceFait) {
            return $this->error('Service fait introuvable.', 404);
        }

        $anciennesValeurs = $serviceFait->toArray();
        $donnees = collect($request->validated())->except('commentaire')->toArray();
        $serviceFait->fill($donnees);
        $serviceFait->statut = 'en_attente';
        $serviceFait->save();

        ServiceFaitHistorique::create([
            'service_fait_id' => $serviceFait->id,
            'utilisateur_id' => $request->user()?->id,
            'action' => 'correction : ' . ($request->validated('commentaire') ?? ''),
            'anciennes_valeurs' => $anciennesValeurs,
            'nouvelles_valeurs' => $serviceFait->fresh()->toArray(),
        ]);

        return $this->success('Service fait corrigé avec succès.', $serviceFait);
    }

    public function historique(string $id)
    {
        $serviceFait = ServiceFait::find($id);

        if (! $serviceFait) {
            return $this->error('Service fait introuvable.', 404);
        }

        $historique = ServiceFaitHistorique::where('service_fait_id', $id)
            ->latest()
            ->paginate(15);

        return $this->success('Historique du service fait.', $historique);
    }

    public function rechercher(Request $request)
    {
        $query = ServiceFait::query();

        if ($request->filled('q')) {
            $terme = $request->query('q');
            $query->where(function ($q) use ($terme) {
                $q->where('description', 'like', "%{$terme}%")
                    ->orWhere('lieu', 'like', "%{$terme}%");
            });
        }

        if ($request->filled('statut')) {
            $query->where('statut', $request->query('statut'));
        }

        if ($request->filled('date_debut')) {
            $query->whereDate('date_debut', '>=', $request->query('date_debut'));
        }

        if ($request->filled('date_fin')) {
            $query->whereDate('date_fin', '<=', $request->query('date_fin'));
        }

        $resultats = $query->latest()->paginate($request->integer('per_page', 15));

        return $this->success('Résultats de la recherche.', $resultats);
    }

    public function export(Request $request)
    {
        $query = ServiceFait::query();

        if ($request->filled('statut')) {
            $query->where('statut', $request->query('statut'));
        }

        $serviceFaits = $query->get();

        return $this->success('Export des services faits.', [
            'total' => $serviceFaits->count(),
            'items' => $serviceFaits,
        ]);
    }

    /**
     * Vérifie la conformité d'un service fait (dates cohérentes, pièces requises).
     */
    public function verifierConformite(string $id)
    {
        $serviceFait = ServiceFait::find($id);

        if (! $serviceFait) {
            return $this->error('Service fait introuvable.', 404);
        }

        $erreurs = [];

        if (! $serviceFait->date_debut || ! $serviceFait->date_fin) {
            $erreurs[] = 'Dates de début/fin manquantes.';
        } elseif ($serviceFait->date_fin < $serviceFait->date_debut) {
            $erreurs[] = 'La date de fin est antérieure à la date de début.';
        }

        if (! $serviceFait->nombre_jours || $serviceFait->nombre_jours <= 0) {
            $erreurs[] = 'Le nombre de jours doit être supérieur à zéro.';
        }

        $conforme = empty($erreurs);

        return $this->success(
            $conforme ? 'Service fait conforme.' : 'Service fait non conforme.',
            ['conforme' => $conforme, 'erreurs' => $erreurs]
        );
    }
}
