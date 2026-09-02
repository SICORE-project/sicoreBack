<?php

namespace App\Http\Controllers;

use App\Models\bultins;
use App\Models\DelegationCredit;
use App\Models\ias;
use App\Models\VentilationDelegation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Ecran SICORE "Engagements vs paie" - HORS FINPRONET.
 *
 * Rapproche ce qui a ete engage sur les credits delegues et ce qui a
 * effectivement ete paye, pour faire apparaitre l'ecart. FINPRONET n'a pas cet
 * ecran : il vient d'un besoin SICORE, et il est volontairement separe de
 * "Edition des engagements par delegation" qui, lui, est un etat FINPRONET pur.
 *
 * DEUX LIMITES CONNUES, a lever cote modele de donnees :
 *
 *  1. Maille geographique. Le cote engagements porte corps / IA / IEF sur la
 *     ventilation ; le cote paie ne porte que ia_id sur le bulletin. Le
 *     rapprochement n'est donc fiable qu'au niveau IA. Les filtres corps et IEF
 *     ne sont volontairement PAS exposes ici : les appliquer d'un seul cote
 *     produirait un ecart faux. Ils pourront l'etre quand bultins portera
 *     ief_id et corps_enseignant_id.
 *
 *  2. Maille temporelle. delegation_credits.periode_paie est un texte libre
 *     ("octobre") alors que bultins.mois_validite est une date. Aucune
 *     correspondance automatique n'est faite : les deux periodes se saisissent
 *     separement (periode_paie et periode_bulletin) et l'ecran affiche ce qui a
 *     ete demande de chaque cote. Deviner la correspondance reviendrait a
 *     inventer une regle de gestion.
 */
class ComparaisonEngagementPaieController extends Controller
{
    /**
     * Taux de charges patronales.
     * A remonter dans les Parametres generaux : c'est un parametre de gestion,
     * pas une constante technique.
     */
    private const TAUX_CHARGES_EMPLOYEUR = 0.16;

    public function filtres()
    {
        return response()->json([
            'annees_academiques' => DelegationCredit::query()
                ->distinct()
                ->orderBy('annee_academique')
                ->pluck('annee_academique'),

            'periodes_paie' => DelegationCredit::query()
                ->whereNotNull('periode_paie')
                ->distinct()
                ->orderBy('periode_paie')
                ->pluck('periode_paie'),

            'periodes_bulletin' => bultins::query()
                ->select(DB::raw("DATE_FORMAT(mois_validite, '%Y-%m') as periode"))
                ->distinct()
                ->orderBy('periode')
                ->pluck('periode'),

            'ias' => ias::orderBy('code')->get(['id', 'code', 'libelle']),
        ]);
    }

    public function index(Request $request)
    {
        $v = $request->validate([
            'annee_academique' => 'nullable|string|max:20',
            'periode_paie'     => 'nullable|string|max:50',
            'periode_bulletin' => 'nullable|string|max:20',
            'ia_id'            => 'nullable|exists:ias,id',
        ]);

        $engagements = $this->cotesEngagements($v);
        $paie        = $this->cotePaie($v);

        $ecart = $engagements['total_engagement'] - $paie['total_net'];

        return response()->json([
            'filtres' => [
                'annee_academique' => $v['annee_academique'] ?? null,
                'periode_paie'     => $v['periode_paie'] ?? null,
                'periode_bulletin' => $v['periode_bulletin'] ?? null,
                'ia_id'            => $v['ia_id'] ?? null,
            ],

            'engagements' => $engagements,
            'paie'        => $paie,

            'comparaison' => [
                'total_engagement' => $engagements['total_engagement'],
                'total_net_paye'   => $paie['total_net'],
                'ecart'            => round($ecart, 2),
                'sens'             => $ecart >= 0 ? 'sur_engagement' : 'sous_engagement',
            ],

            'avertissements' => $this->avertissements($v),
        ]);
    }

    /**
     * Cote engagements : lignes de ventilation.
     * Le filtre IA est applique ICI aussi, c'est ce qui rend l'ecart comparable.
     */
    private function cotesEngagements(array $v): array
    {
        $query = VentilationDelegation::query()
            ->when($v['ia_id'] ?? null, fn ($q, $id) => $q->where('ia_id', $id))
            ->when(
                ! empty($v['annee_academique']) || ! empty($v['periode_paie']),
                fn ($q) => $q->whereHas('delegationCredit', function ($d) use ($v) {
                    if (! empty($v['annee_academique'])) {
                        $d->where('annee_academique', $v['annee_academique']);
                    }
                    if (! empty($v['periode_paie'])) {
                        $d->where('periode_paie', $v['periode_paie']);
                    }
                })
            );

        $agregats = (clone $query)
            ->selectRaw('COUNT(*) as nombre_lignes')
            ->selectRaw('COUNT(DISTINCT delegation_credit_id) as nombre_delegations')
            ->selectRaw('COALESCE(SUM(montant), 0) as total_montant')
            ->selectRaw('COALESCE(SUM(montant_engagement), 0) as total_engagement')
            ->first();

        $montant    = (float) $agregats->total_montant;
        $engagement = (float) $agregats->total_engagement;

        return [
            'nombre_delegations' => (int) $agregats->nombre_delegations,
            'nombre_lignes'      => (int) $agregats->nombre_lignes,
            'total_montant'      => round($montant, 2),
            'total_engagement'   => round($engagement, 2),
            'total_reste'        => round($montant - $engagement, 2),
        ];
    }

    /** Cote paie : bulletins, filtres sur la meme IA. */
    private function cotePaie(array $v): array
    {
        $bulletins = bultins::with('details')
            ->when($v['ia_id'] ?? null, fn ($q, $id) => $q->where('ia_id', $id))
            ->when($v['periode_bulletin'] ?? null, function ($q, $periode) {
                $q->whereRaw("DATE_FORMAT(mois_validite, '%Y-%m') = ?", [$periode]);
            })
            ->get();

        $totalBrut     = 0.0;
        $totalRetenues = 0.0;
        $totalNet      = 0.0;
        $agents        = [];

        foreach ($bulletins as $b) {
            $totalBrut     += (float) $b->details->sum('montant_gains');
            $totalRetenues += (float) $b->details->sum('montant_retenus');
            $totalNet      += (float) $b->net_a_payer;
            $agents[$b->enseignant_id] = true;
        }

        return [
            'nombre_agents'      => count($agents),
            'nombre_bulletins'   => $bulletins->count(),
            'total_brut'         => round($totalBrut, 2),
            'total_retenues'     => round($totalRetenues, 2),
            'total_net'          => round($totalNet, 2),
            'charges_employeur'  => round($totalBrut * self::TAUX_CHARGES_EMPLOYEUR, 2),
            'taux_charges'       => self::TAUX_CHARGES_EMPLOYEUR * 100,
        ];
    }

    /**
     * Rend explicites les conditions dans lesquelles l'ecart n'est pas
     * interpretable. Mieux vaut un avertissement affiche qu'un chiffre faux
     * pris pour argent comptant.
     */
    private function avertissements(array $v): array
    {
        $messages = [];

        if (empty($v['ia_id'])) {
            $messages[] = "Aucune IA selectionnee : l'ecart porte sur l'ensemble du territoire.";
        }

        $aPeriodeEngagement = ! empty($v['periode_paie']);
        $aPeriodeBulletin   = ! empty($v['periode_bulletin']);

        if ($aPeriodeEngagement !== $aPeriodeBulletin) {
            $messages[] = "Une seule des deux periodes est renseignee : les deux cotes ne "
                . "couvrent pas le meme intervalle, l'ecart n'est pas interpretable.";
        }

        $messages[] = "Le rapprochement n'est fiable qu'au niveau IA : le bulletin ne porte "
            . "ni IEF ni corps d'enseignant.";

        return $messages;
    }
}
