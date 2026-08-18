<?php

namespace App\Http\Controllers;

use App\Models\DelegationCredit;
use App\Models\PaiementSalaire;
use Illuminate\Http\Request;

class EtatCreditsController extends Controller
{
    public function index(Request $request)
    {
        $query = DelegationCredit::with(['structure', 'service']);

        if ($request->structure_id) {
            $query->where('structure_id', $request->structure_id);
        }
        if ($request->service_id) {
            $query->where('service_id', $request->service_id);
        }
        if ($request->statut) {
            $query->where('statut', $request->statut);
        }
        if ($request->delegation_id) {
            $query->where('id', $request->delegation_id);
        }
        if ($request->date_debut) {
            $query->where('date_delegation', '>=', $request->date_debut);
        }
        if ($request->date_fin) {
            $query->where(function ($q) use ($request) {
                $q->where('date_fin', '<=', $request->date_fin)
                  ->orWhereNull('date_fin');
            });
        }
        if ($request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('reference', 'like', "%{$search}%")
                  ->orWhere('objet', 'like', "%{$search}%");
            });
        }

        $delegations = $query->orderByDesc('created_at')->get();

        $totalInitial = $delegations->sum('montant_initial');
        $totalDisponible = $delegations->sum('montant_disponible');
        $totalEngage = $delegations->sum('montant_engage');
        $totalConsomme = $delegations->sum('montant_consomme');
        $totalSolde = $delegations->sum('solde');

        $tauxConsommation = $totalDisponible > 0
            ? round(($totalConsomme / $totalDisponible) * 100, 1)
            : 0;
        $tauxEngagement = $totalDisponible > 0
            ? round(($totalEngage / $totalDisponible) * 100, 1)
            : 0;

        $lignes = $delegations->map(function ($d) {
            $tauxConso = $d->montant_disponible > 0
                ? round(($d->montant_consomme / $d->montant_disponible) * 100, 1)
                : 0;
            $tauxEng = $d->montant_disponible > 0
                ? round(($d->montant_engage / $d->montant_disponible) * 100, 1)
                : 0;
            $pctSolde = $d->montant_disponible > 0
                ? round(($d->solde / $d->montant_disponible) * 100, 1)
                : 0;

            return [
                'id' => $d->id,
                'reference' => $d->reference,
                'objet' => $d->objet,
                'structure' => $d->structure?->nom ?? '-',
                'service' => $d->service?->nom ?? '-',
                'annee_academique' => $d->annee_academique,
                'date_delegation' => $d->date_delegation?->format('Y-m-d'),
                'date_fin' => $d->date_fin?->format('Y-m-d'),
                'statut' => $d->statut,
                'montant_initial' => round($d->montant_initial, 2),
                'montant_disponible' => round($d->montant_disponible, 2),
                'montant_engage' => round($d->montant_engage, 2),
                'montant_consomme' => round($d->montant_consomme, 2),
                'solde' => round($d->solde, 2),
                'taux_consommation' => $tauxConso,
                'taux_engagement' => $tauxEng,
                'pct_solde' => $pctSolde,
                'nb_paiements' => $d->paiementSalaires()->count(),
            ];
        });

        return response()->json([
            'totaux' => [
                'nombre_delegations' => $delegations->count(),
                'montant_initial' => round($totalInitial, 2),
                'montant_disponible' => round($totalDisponible, 2),
                'montant_engage' => round($totalEngage, 2),
                'montant_consomme' => round($totalConsomme, 2),
                'solde' => round($totalSolde, 2),
                'taux_consommation' => $tauxConsommation,
                'taux_engagement' => $tauxEngagement,
            ],
            'lignes' => $lignes,
        ]);
    }

    public function filtres()
    {
        $structures = \App\Models\Structure::with('services')->orderBy('nom')->get()
            ->map(fn ($s) => [
                'id' => $s->id,
                'nom' => $s->nom,
                'services' => $s->services->map(fn ($svc) => [
                    'id' => $svc->id,
                    'nom' => $svc->nom,
                ]),
            ]);

        $delegations = DelegationCredit::select('id', 'reference', 'objet')
            ->orderBy('reference')
            ->get();

        $periodes = DelegationCredit::selectRaw('DISTINCT YEAR(date_delegation) as annee')
            ->whereNotNull('date_delegation')
            ->orderByDesc('annee')
            ->pluck('annee')
            ->filter();

        return response()->json([
            'structures' => $structures,
            'delegations' => $delegations,
            'periodes' => $periodes,
        ]);
    }

    public function detail($id)
    {
        $delegation = DelegationCredit::with(['structure', 'service'])->findOrFail($id);

        $paiements = PaiementSalaire::where('delegation_credit_id', $id)
            ->with('bultin.enseignant.user')
            ->orderByDesc('date_paiement')
            ->get()
            ->map(function ($p) {
                return [
                    'id' => $p->id,
                    'nom_agent' => $p->nom_agent,
                    'mois' => $p->mois,
                    'montant' => round($p->montant, 2),
                    'date_paiement' => $p->date_paiement?->format('Y-m-d'),
                    'bulletin_ref' => $p->bultin?->reference ?? '-',
                    'type' => $p->bultin_id ? 'bulletin' : 'manuel',
                ];
            });

        $totalPaiements = $paiements->sum('montant');

        $coherence = abs($delegation->montant_consomme - $totalPaiements) < 0.01;

        return response()->json([
            'delegation' => [
                'id' => $delegation->id,
                'reference' => $delegation->reference,
                'objet' => $delegation->objet,
                'structure' => $delegation->structure?->nom ?? '-',
                'service' => $delegation->service?->nom ?? '-',
                'montant_initial' => round($delegation->montant_initial, 2),
                'montant_disponible' => round($delegation->montant_disponible, 2),
                'montant_engage' => round($delegation->montant_engage, 2),
                'montant_consomme' => round($delegation->montant_consomme, 2),
                'solde' => round($delegation->solde, 2),
                'statut' => $delegation->statut,
            ],
            'paiements' => $paiements,
            'total_paiements' => round($totalPaiements, 2),
            'nombre_paiements' => $paiements->count(),
            'coherence' => $coherence,
        ]);
    }
}
