<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDelegationCreditRequest;
use App\Http\Requests\UpdateDelegationCreditRequest;
use App\Http\Resources\DelegationCreditResource;
use App\Models\DelegationCredit;
use App\Models\PaiementSalaire;

class DelegationCreditController extends Controller
{
    public function index()
    {
        $delegations = DelegationCredit::with(['structure', 'service'])->get();

        return DelegationCreditResource::collection($delegations);
    }

    public function store(StoreDelegationCreditRequest $request)
    {
        $validated = $request->validated();

        $montantInitial = $validated['montant_initial'] ?? $validated['montant_disponible'];

        $delegation = DelegationCredit::create([
            'annee_academique'   => $validated['annee_academique'],
            'reference'          => $validated['reference'],
            'objet'              => $validated['objet'],
            'structure_id'       => $validated['structure_id'],
            'service_id'         => $validated['service_id'],
            'montant_initial'    => $montantInitial,
            'montant_disponible' => $validated['montant_disponible'],
            'montant_engage'     => 0,
            'montant_consomme'   => 0,
            'solde'              => $validated['montant_disponible'],
            'date_delegation'    => $validated['date_delegation'],
            'date_fin'           => $validated['date_fin'] ?? null,
            'statut'             => 'En attente',
        ]);

        return response()->json([
            'message' => 'Délégation créée avec succès.',
            'data' => new DelegationCreditResource($delegation->load(['structure', 'service']))
        ], 201);
    }

    public function show(string $id)
    {
        $delegation = DelegationCredit::with(['structure', 'service'])->findOrFail($id);

        return new DelegationCreditResource($delegation);
    }

    public function update(UpdateDelegationCreditRequest $request, string $id)
    {
        $delegation = DelegationCredit::findOrFail($id);

        $delegation->update($request->validated());

        return response()->json([
            'message' => 'Délégation modifiée avec succès.',
            'data' => new DelegationCreditResource($delegation->load(['structure', 'service']))
        ]);
    }

    public function destroy(string $id)
    {
        $delegation = DelegationCredit::findOrFail($id);

        $delegation->delete();

        return response()->json([
            'message' => 'Délégation supprimée avec succès.'
        ]);
    }

    public function affecter(\Illuminate\Http\Request $request, $id)
    {
        $request->validate([
            'structure_id' => 'required|exists:structures,id',
            'service_id'   => 'nullable|exists:services,id',
        ]);

        $delegation = DelegationCredit::findOrFail($id);

        if ($request->service_id) {
            $service = \App\Models\Service::findOrFail($request->service_id);
            if ($service->structure_id != $request->structure_id) {
                return response()->json([
                    'message' => 'Le service sélectionné n\'appartient pas à cette structure.',
                    'errors' => ['service_id' => ['Le service ne correspond pas à la structure choisie.']]
                ], 422);
            }
        }

        if ($delegation->structure_id == $request->structure_id
            && $delegation->service_id == $request->service_id) {
            return response()->json([
                'message' => 'Cette délégation est déjà affectée à cette structure/service.',
            ], 409);
        }

        $delegation->structure_id = $request->structure_id;
        $delegation->service_id = $request->service_id;
        $delegation->save();

        return response()->json([
            'message' => 'Affectation réalisée avec succès.',
            'data' => new DelegationCreditResource($delegation->load(['structure', 'service']))
        ]);
    }

    public function engager(\Illuminate\Http\Request $request, $id)
    {
        $request->validate([
            'montant' => 'required|numeric',
            'motif' => 'required|string|max:255',
            'date_engagement' => 'nullable|date',
            'reference_operation' => 'nullable|string|max:100',
        ]);

        $montant = (float) $request->montant;

        if ($montant <= 0) {
            return response()->json([
                'message' => 'Le montant doit être supérieur à zéro.',
            ], 422);
        }

        $delegation = DelegationCredit::findOrFail($id);

        $creditDisponible = $delegation->montant_disponible - $delegation->montant_engage;

        if ($montant > $creditDisponible) {
            return response()->json([
                'message' => 'L\'engagement dépasse le crédit disponible. Reste : ' .
                    number_format($creditDisponible, 0, ',', ' ') . ' FCFA.',
                'credit_disponible' => round($creditDisponible, 2),
            ], 422);
        }

        $engagement = \App\Models\Engagement::create([
            'delegation_credit_id' => $delegation->id,
            'motif' => $request->motif,
            'montant' => $montant,
            'date_engagement' => $request->date_engagement ?? now()->toDateString(),
            'reference_operation' => $request->reference_operation,
        ]);

        $delegation->montant_engage = $delegation->engagements()->sum('montant');
        $delegation->solde = $delegation->montant_disponible - $delegation->montant_engage;
        $delegation->save();

        return response()->json([
            'message' => 'Engagement enregistré avec succès.',
            'engagement' => $engagement,
            'data' => new DelegationCreditResource($delegation),
        ]);
    }

    public function historiqueEngagements(\Illuminate\Http\Request $request, $id)
    {
        $delegation = DelegationCredit::with(['structure', 'service'])->findOrFail($id);

        $query = $delegation->engagements()->orderByDesc('date_engagement');

        if ($request->date_debut) {
            $query->where('date_engagement', '>=', $request->date_debut);
        }
        if ($request->date_fin_filtre) {
            $query->where('date_engagement', '<=', $request->date_fin_filtre);
        }

        $engagements = $query->get();

        return response()->json([
            'delegation' => [
                'id' => $delegation->id,
                'reference' => $delegation->reference,
                'objet' => $delegation->objet,
                'structure' => $delegation->structure?->nom,
                'service' => $delegation->service?->nom,
                'montant_initial' => round($delegation->montant_initial, 2),
                'montant_disponible' => round($delegation->montant_disponible, 2),
                'montant_engage' => round($delegation->montant_engage, 2),
                'solde' => round($delegation->solde, 2),
                'taux_engagement' => $delegation->montant_disponible > 0
                    ? round(($delegation->montant_engage / $delegation->montant_disponible) * 100, 1)
                    : 0,
            ],
            'engagements' => $engagements,
            'total' => round($engagements->sum('montant'), 2),
            'nombre' => $engagements->count(),
        ]);
    }

    public function tousEngagements(\Illuminate\Http\Request $request)
    {
        $query = \App\Models\Engagement::with(['delegationCredit.structure', 'delegationCredit.service'])
            ->orderByDesc('date_engagement');

        if ($request->structure_id) {
            $query->whereHas('delegationCredit', fn ($q) => $q->where('structure_id', $request->structure_id));
        }
        if ($request->service_id) {
            $query->whereHas('delegationCredit', fn ($q) => $q->where('service_id', $request->service_id));
        }
        if ($request->date_debut) {
            $query->where('date_engagement', '>=', $request->date_debut);
        }
        if ($request->date_fin_filtre) {
            $query->where('date_engagement', '<=', $request->date_fin_filtre);
        }

        $engagements = $query->get()->map(fn ($e) => [
            'id' => $e->id,
            'motif' => $e->motif,
            'montant' => round($e->montant, 2),
            'date_engagement' => $e->date_engagement->format('Y-m-d'),
            'reference_operation' => $e->reference_operation,
            'delegation_reference' => $e->delegationCredit->reference,
            'delegation_id' => $e->delegation_credit_id,
            'structure' => $e->delegationCredit->structure?->nom ?? '-',
            'service' => $e->delegationCredit->service?->nom ?? '-',
        ]);

        return response()->json([
            'engagements' => $engagements,
            'total' => round($engagements->sum('montant'), 2),
            'nombre' => $engagements->count(),
        ]);
    }

    public function consommer(\Illuminate\Http\Request $request, $id)
    {
        $request->validate([
            'montant' => 'required|numeric',
            'nom_agent' => 'required|string|max:255',
            'mois' => 'required|string|max:20',
            'date_paiement' => 'nullable|date',
        ]);

        $montant = (float) $request->montant;

        if ($montant <= 0) {
            return response()->json([
                'message' => 'Le montant doit être supérieur à zéro.',
            ], 422);
        }

        $delegation = DelegationCredit::findOrFail($id);

        $resteDisponible = $delegation->montant_disponible - $delegation->montant_consomme;

        if ($montant > $resteDisponible) {
            return response()->json([
                'message' => 'Le paiement dépasse le solde disponible. Reste : ' .
                    number_format($resteDisponible, 0, ',', ' ') . ' FCFA.',
                'solde_disponible' => round($resteDisponible, 2),
            ], 422);
        }

        $paiement = $delegation->paiementSalaires()->create([
            'nom_agent' => $request->nom_agent,
            'mois' => $request->mois,
            'montant' => $montant,
            'date_paiement' => $request->date_paiement ?? now()->toDateString(),
        ]);

        $delegation->montant_consomme = $delegation->paiementSalaires()->sum('montant');
        $delegation->solde = $delegation->montant_disponible - $delegation->montant_consomme;
        $delegation->save();

        return response()->json([
            'message' => 'Paiement enregistré. Consommation mise à jour.',
            'paiement' => $paiement,
            'data' => new DelegationCreditResource($delegation),
        ]);
    }

    public function solde($id)
    {
        $delegation = DelegationCredit::findOrFail($id);

        $taux = $delegation->montant_disponible > 0
            ? round(($delegation->montant_consomme / $delegation->montant_disponible) * 100, 1)
            : 0;

        return response()->json([
            'reference' => $delegation->reference,
            'montant_initial' => $delegation->montant_initial,
            'montant_disponible' => $delegation->montant_disponible,
            'montant_engage' => $delegation->montant_engage,
            'montant_consomme' => $delegation->montant_consomme,
            'solde' => $delegation->solde,
            'taux_consommation' => $taux,
        ]);
    }

    public function ajouterPaiement(\Illuminate\Http\Request $request, $id)
    {
        return $this->consommer($request, $id);
    }

    public function historiqueConsommations(\Illuminate\Http\Request $request, $id)
    {
        $delegation = DelegationCredit::with(['structure', 'service'])->findOrFail($id);

        $query = $delegation->paiementSalaires()->orderByDesc('date_paiement');

        if ($request->date_debut) {
            $query->where('date_paiement', '>=', $request->date_debut);
        }
        if ($request->date_fin_filtre) {
            $query->where('date_paiement', '<=', $request->date_fin_filtre);
        }
        if ($request->nom_agent) {
            $query->where('nom_agent', 'like', '%' . $request->nom_agent . '%');
        }
        if ($request->mois_filtre) {
            $query->where('mois', 'like', '%' . $request->mois_filtre . '%');
        }

        $paiements = $query->get();

        return response()->json([
            'delegation' => [
                'id' => $delegation->id,
                'reference' => $delegation->reference,
                'objet' => $delegation->objet,
                'structure' => $delegation->structure?->nom,
                'service' => $delegation->service?->nom,
                'montant_disponible' => round($delegation->montant_disponible, 2),
                'montant_consomme' => round($delegation->montant_consomme, 2),
                'solde' => round($delegation->solde, 2),
                'taux_consommation' => $delegation->montant_disponible > 0
                    ? round(($delegation->montant_consomme / $delegation->montant_disponible) * 100, 1)
                    : 0,
            ],
            'paiements' => $paiements,
            'total' => round($paiements->sum('montant'), 2),
            'nombre' => $paiements->count(),
        ]);
    }

    public function tousConsommations(\Illuminate\Http\Request $request)
    {
        $query = PaiementSalaire::with(['delegationCredit.structure', 'delegationCredit.service'])
            ->orderByDesc('date_paiement');

        if ($request->structure_id) {
            $query->whereHas('delegationCredit', fn ($q) => $q->where('structure_id', $request->structure_id));
        }
        if ($request->service_id) {
            $query->whereHas('delegationCredit', fn ($q) => $q->where('service_id', $request->service_id));
        }
        if ($request->date_debut) {
            $query->where('date_paiement', '>=', $request->date_debut);
        }
        if ($request->date_fin_filtre) {
            $query->where('date_paiement', '<=', $request->date_fin_filtre);
        }
        if ($request->nom_agent) {
            $query->where('nom_agent', 'like', '%' . $request->nom_agent . '%');
        }

        $paiements = $query->get()->map(fn ($p) => [
            'id' => $p->id,
            'nom_agent' => $p->nom_agent,
            'mois' => $p->mois,
            'montant' => round($p->montant, 2),
            'date_paiement' => $p->date_paiement,
            'delegation_reference' => $p->delegationCredit->reference,
            'delegation_id' => $p->delegation_credit_id,
            'structure' => $p->delegationCredit->structure?->nom ?? '-',
            'service' => $p->delegationCredit->service?->nom ?? '-',
        ]);

        return response()->json([
            'paiements' => $paiements,
            'total' => round($paiements->sum('montant'), 2),
            'nombre' => $paiements->count(),
        ]);
    }

    public function definirMontantDisponible(\Illuminate\Http\Request $request, $id)
    {
        $delegation = DelegationCredit::findOrFail($id);

        $request->validate([
            'montant_disponible' => 'required|numeric',
        ]);

        $montant = (float) $request->montant_disponible;

        if ($montant <= 0) {
            return response()->json([
                'message' => 'Le montant disponible doit être supérieur à zéro.',
                'errors' => ['montant_disponible' => ['Le montant doit être strictement positif.']],
            ], 422);
        }

        if ($delegation->montant_initial && $montant > $delegation->montant_initial) {
            return response()->json([
                'message' => 'Le montant disponible ne peut pas dépasser le montant initial (' .
                    number_format($delegation->montant_initial, 0, ',', ' ') . ' FCFA).',
                'errors' => ['montant_disponible' => ['Dépasse le montant initial.']],
            ], 422);
        }

        if ($montant < $delegation->montant_consomme) {
            return response()->json([
                'message' => 'Le montant disponible ne peut pas être inférieur au montant déjà consommé (' .
                    number_format($delegation->montant_consomme, 0, ',', ' ') . ' FCFA).',
                'errors' => ['montant_disponible' => ['Inférieur au montant consommé.']],
            ], 422);
        }

        $delegation->montant_disponible = $montant;
        $delegation->solde = $montant - $delegation->montant_consomme;
        $delegation->save();

        return response()->json([
            'message' => 'Montant disponible mis à jour avec succès.',
            'data' => new DelegationCreditResource($delegation->load(['structure', 'service'])),
        ]);
    }

    public function etatCredits($id)
    {
        $delegation = DelegationCredit::with('paiementSalaires')->findOrFail($id);

        return response()->json([
            'reference' => $delegation->reference,
            'annee_academique' => $delegation->annee_academique,
            'objet' => $delegation->objet,
            'montant_initial' => $delegation->montant_initial,
            'montant_disponible' => $delegation->montant_disponible,
            'montant_engage' => $delegation->montant_engage,
            'montant_consomme' => $delegation->montant_consomme,
            'solde' => $delegation->solde,
            'date_delegation' => $delegation->date_delegation,
            'date_fin' => $delegation->date_fin,
            'statut' => $delegation->statut,
            'paiements' => $delegation->paiementSalaires
        ]);
    }
}
