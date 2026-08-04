<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\DelegationCredit;

class DelegationCreditController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
         $delegations = DelegationCredit::with(['structure', 'service'])->get();

         return response()->json($delegations);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
       $validated = $request->validate([
        'annee_academique' => 'required|string|max:20',
        'reference' => 'required|string|unique:delegation_credits,reference',
        'objet' => 'required|string|max:255',
        'structure_id' => 'required|exists:structures,id',
        'service_id' => 'required|exists:services,id',
        'montant_disponible' => 'required|numeric|min:0',
        'date_delegation' => 'required|date',
    ]);

    $delegation = DelegationCredit::create([
        'annee_academique'   => $validated['annee_academique'],
        'reference'          => $validated['reference'],
        'objet'              => $validated['objet'],
        'structure_id'       => $validated['structure_id'],
        'service_id'         => $validated['service_id'],
        'montant_disponible' => $validated['montant_disponible'],
        'montant_engage'     => 0,
        'montant_consomme'   => 0,
        'solde'              => $validated['montant_disponible'],
        'date_delegation'    => $validated['date_delegation'],
        'statut'             => 'En attente',
    ]);

    return response()->json([
        'message' => 'Délégation créée avec succès.',
        'data' => $delegation
    ], 201); //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $delegation = DelegationCredit::findOrFail($id);

    return response()->json($delegation);  //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $delegation = DelegationCredit::findOrFail($id);

    $delegation->update($request->all());

    return response()->json([
        'message' => 'Délégation modifiée avec succès.',
        'data' => $delegation
    ]); //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
         $delegation = DelegationCredit::findOrFail($id);

    $delegation->delete();

    return response()->json([
        'message' => 'Délégation supprimée avec succès.'
    ]); //
    }
    /**
     * Affect the specified resource in storage.
     */
public function affecter(Request $request, $id)
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
        'data' => $delegation
    ]);
}
/**
     * Engager montant to the specified resource in storage.
     */

public function engager(Request $request, $id)
{
    $request->validate([
        'montant' => 'required|numeric|min:0'
    ]);

    $delegation = DelegationCredit::findOrFail($id);

    $delegation->montant_engage += $request->montant;
    $delegation->solde =
        $delegation->montant_disponible -
        $delegation->montant_engage;

    $delegation->save();

    return response()->json([
        'message' => 'Montant engagé mis à jour.',
        'data' => $delegation
    ]);
}
/**
     * Consommer montant to the specified resource in storage.
     */
public function consommer(Request $request, $id)
{
    $request->validate([
        'montant' => 'required|numeric|min:0'
    ]);

    $delegation = DelegationCredit::findOrFail($id);

    $delegation->montant_consomme += $request->montant;

    $delegation->solde =
        $delegation->montant_disponible -
        $delegation->montant_consomme;

    $delegation->save();

    return response()->json([
        'message' => 'Montant consommé mis à jour.',
        'data' => $delegation
    ]);
}
/**
     * Consulter solde restant to the specified resource in storage.
     */
   public function solde($id)
{
    $delegation = DelegationCredit::findOrFail($id);

    return response()->json([
        'reference' => $delegation->reference,
        'montant_disponible' => $delegation->montant_disponible,
        'montant_engage' => $delegation->montant_engage,
        'montant_consomme' => $delegation->montant_consomme,
        'solde' => $delegation->solde,
    ]);
} 
//Ajouter un paiement pour une délégation de crédit
public function ajouterPaiement(Request $request, $id)
{
    $request->validate([
        'nom_agent' => 'required|string|max:255',
        'mois' => 'required|string|max:20',
        'montant' => 'required|numeric|min:0',
        'date_paiement' => 'required|date',
    ]);

    $delegation = DelegationCredit::findOrFail($id);

    // Vérifier que le solde est suffisant
    if ($request->montant > $delegation->solde) {
        return response()->json([
            'message' => 'Le montant dépasse le solde disponible.'
        ], 400);
    }

    // Création du paiement
    $paiement = $delegation->paiementSalaires()->create([
        'nom_agent' => $request->nom_agent,
        'mois' => $request->mois,
        'montant' => $request->montant,
        'date_paiement' => $request->date_paiement,
    ]);

    // Mise à jour des montants
   $delegation->montant_engage += $paiement->montant;
$delegation->montant_consomme += $paiement->montant;

$delegation->solde =
    $delegation->montant_disponible -
    $delegation->montant_consomme;

$delegation->save();

    return response()->json([
        'message' => 'Paiement enregistré avec succès.',
        'paiement' => $paiement,
        'delegation' => $delegation
    ], 201);
}

//Etat des crédits pour une délégation de crédit
public function etatCredits($id)
{
    $delegation = DelegationCredit::with('paiementSalaires')->findOrFail($id);

    return response()->json([
        'reference' => $delegation->reference,
        'annee_academique' => $delegation->annee_academique,
        'montant_disponible' => $delegation->montant_disponible,
        'montant_engage' => $delegation->montant_engage,
        'montant_consomme' => $delegation->montant_consomme,
        'solde' => $delegation->solde,
        'paiements' => $delegation->paiementSalaires
    ]);
}

}

