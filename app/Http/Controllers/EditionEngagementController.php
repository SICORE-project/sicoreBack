<?php

namespace App\Http\Controllers;

use App\Models\bultins;
use App\Models\DelegationCredit;
use App\Models\ias;
use App\Models\iefs;
use App\Models\etablissements;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EditionEngagementController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'annee_academique' => 'nullable|string',
            'periode' => 'nullable|string',
            'ia_id' => 'nullable|exists:ias,id',
            'ief_id' => 'nullable|exists:iefs,id',
            'etablissement_id' => 'nullable|exists:etablissements,id',
        ]);

        $iaIds = $this->resolveIaIds($request);

        $engagements = $this->getEngagements($request, $iaIds);
        $paie = $this->getPaie($request, $iaIds);

        return response()->json([
            'filtres' => [
                'annee_academique' => $request->annee_academique,
                'periode' => $request->periode,
                'ia_id' => $request->ia_id,
                'ief_id' => $request->ief_id,
                'etablissement_id' => $request->etablissement_id,
            ],
            'engagements' => $engagements,
            'paie' => $paie,
            'comparaison' => [
                'total_engage' => $engagements['total_engage'],
                'total_net_paye' => $paie['total_net'],
                'ecart' => round($engagements['total_engage'] - $paie['total_net'], 2),
            ],
        ]);
    }

    public function filtres()
    {
        $annees = DelegationCredit::distinct()->pluck('annee_academique')->sort()->values();

        $periodes = bultins::select(DB::raw("DATE_FORMAT(mois_validite, '%Y-%m') as periode"))
            ->distinct()
            ->orderBy('periode')
            ->pluck('periode');

        $ias = ias::select('id', 'libelle', 'code')->orderBy('libelle')->get();

        return response()->json([
            'annees_academiques' => $annees,
            'periodes' => $periodes,
            'ias' => $ias,
        ]);
    }

    public function iefsByIa($iaId)
    {
        $iefsList = iefs::where('ia_id', $iaId)
            ->select('id', 'libelle', 'code')
            ->orderBy('libelle')
            ->get();

        return response()->json($iefsList);
    }

    public function etablissementsByIef($iefId)
    {
        $etabs = etablissements::where('ief_id', $iefId)
            ->select('id', 'libelle', 'code', 'niveau')
            ->orderBy('libelle')
            ->get();

        return response()->json($etabs);
    }

    public function details(Request $request)
    {
        $iaIds = $this->resolveIaIds($request);

        $query = bultins::with(['enseignant.user', 'details.rubrique', 'ia'])
            ->when($iaIds, fn ($q) => $q->whereIn('ia_id', $iaIds))
            ->when($request->periode, function ($q) use ($request) {
                $q->whereRaw("DATE_FORMAT(mois_validite, '%Y-%m') = ?", [$request->periode]);
            });

        $bulletins = $query->get();

        $lignes = $bulletins->map(function ($b) {
            $brut = $b->details->sum('montant_gains');
            $retenues = $b->details->sum('montant_retenus');
            $user = $b->enseignant?->user;

            return [
                'matricule' => $b->matricule,
                'nom' => $user ? $user->nom . ' ' . $user->prenom : '-',
                'ia' => $b->ia?->libelle ?? '-',
                'periode' => $b->mois_validite?->format('Y-m'),
                'brut' => round($brut, 2),
                'retenues' => round($retenues, 2),
                'net' => round($b->net_a_payer, 2),
                'charges_employeur' => round($brut * 0.16, 2),
            ];
        });

        return response()->json([
            'lignes' => $lignes,
            'totaux' => [
                'nombre_agents' => $lignes->count(),
                'total_brut' => round($lignes->sum('brut'), 2),
                'total_retenues' => round($lignes->sum('retenues'), 2),
                'total_net' => round($lignes->sum('net'), 2),
                'total_charges' => round($lignes->sum('charges_employeur'), 2),
            ],
        ]);
    }

    private function resolveIaIds(Request $request): ?array
    {
        if ($request->etablissement_id) {
            $etab = etablissements::find($request->etablissement_id);
            if ($etab && $etab->ief) {
                return [$etab->ief->ia_id];
            }
        }

        if ($request->ief_id) {
            $ief = iefs::find($request->ief_id);
            return $ief ? [$ief->ia_id] : null;
        }

        if ($request->ia_id) {
            return [(int) $request->ia_id];
        }

        return null;
    }

    private function getEngagements(Request $request, ?array $iaIds): array
    {
        $query = DelegationCredit::query();

        if ($request->annee_academique) {
            $query->where('annee_academique', $request->annee_academique);
        }

        $delegations = $query->get();

        return [
            'nombre_delegations' => $delegations->count(),
            'total_engage' => round($delegations->sum('montant_engage'), 2),
            'total_consomme' => round($delegations->sum('montant_consomme'), 2),
            'total_disponible' => round($delegations->sum('montant_disponible'), 2),
            'total_solde' => round($delegations->sum('solde'), 2),
        ];
    }

    private function getPaie(Request $request, ?array $iaIds): array
    {
        $query = bultins::with('details')
            ->when($iaIds, fn ($q) => $q->whereIn('ia_id', $iaIds))
            ->when($request->periode, function ($q) use ($request) {
                $q->whereRaw("DATE_FORMAT(mois_validite, '%Y-%m') = ?", [$request->periode]);
            });

        $bulletins = $query->get();

        $totalBrut = 0;
        $totalRetenues = 0;
        $totalNet = 0;
        $agentIds = [];

        foreach ($bulletins as $b) {
            $totalBrut += $b->details->sum('montant_gains');
            $totalRetenues += $b->details->sum('montant_retenus');
            $totalNet += $b->net_a_payer;
            $agentIds[$b->enseignant_id] = true;
        }

        $chargesEmployeur = round($totalBrut * 0.16, 2);

        return [
            'nombre_agents' => count($agentIds),
            'total_brut' => round($totalBrut, 2),
            'total_retenues' => round($totalRetenues, 2),
            'total_net' => round($totalNet, 2),
            'charges_employeur' => $chargesEmployeur,
        ];
    }
}
