<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDelegationCreditRequest;
use App\Http\Requests\UpdateDelegationCreditRequest;
use App\Http\Resources\DelegationCreditResource;
use App\Models\DelegationCredit;

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
            'service_id'   => 'required|exists:services,id',
        ]);

        $delegation = DelegationCredit::findOrFail($id);

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
            'montant' => 'required|numeric|min:0'
        ]);

        $delegation = DelegationCredit::findOrFail($id);

        $delegation->montant_engage += $request->montant;
        $delegation->solde = $delegation->montant_disponible - $delegation->montant_engage;
        $delegation->save();

        return response()->json([
            'message' => 'Montant engagé mis à jour.',
            'data' => new DelegationCreditResource($delegation)
        ]);
    }

    public function consommer(\Illuminate\Http\Request $request, $id)
    {
        $request->validate([
            'montant' => 'required|numeric|min:0'
        ]);

        $delegation = DelegationCredit::findOrFail($id);

        $delegation->montant_consomme += $request->montant;
        $delegation->solde = $delegation->montant_disponible - $delegation->montant_consomme;
        $delegation->save();

        return response()->json([
            'message' => 'Montant consommé mis à jour.',
            'data' => new DelegationCreditResource($delegation)
        ]);
    }

    public function solde($id)
    {
        $delegation = DelegationCredit::findOrFail($id);

        return response()->json([
            'reference' => $delegation->reference,
            'montant_initial' => $delegation->montant_initial,
            'montant_disponible' => $delegation->montant_disponible,
            'montant_engage' => $delegation->montant_engage,
            'montant_consomme' => $delegation->montant_consomme,
            'solde' => $delegation->solde,
        ]);
    }

    public function ajouterPaiement(\Illuminate\Http\Request $request, $id)
    {
        $request->validate([
            'nom_agent' => 'required|string|max:255',
            'mois' => 'required|string|max:20',
            'montant' => 'required|numeric|min:0',
            'date_paiement' => 'required|date',
        ]);

        $delegation = DelegationCredit::findOrFail($id);

        if ($request->montant > $delegation->solde) {
            return response()->json([
                'message' => 'Le montant dépasse le solde disponible.'
            ], 400);
        }

        $paiement = $delegation->paiementSalaires()->create([
            'nom_agent' => $request->nom_agent,
            'mois' => $request->mois,
            'montant' => $request->montant,
            'date_paiement' => $request->date_paiement,
        ]);

        $delegation->montant_engage += $paiement->montant;
        $delegation->montant_consomme += $paiement->montant;
        $delegation->solde = $delegation->montant_disponible - $delegation->montant_consomme;
        $delegation->save();

        return response()->json([
            'message' => 'Paiement enregistré avec succès.',
            'paiement' => $paiement,
            'delegation' => new DelegationCreditResource($delegation)
        ], 201);
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
