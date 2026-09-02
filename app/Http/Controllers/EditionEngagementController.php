<?php

namespace App\Http\Controllers;

use App\Models\corps_enseignants;
use App\Models\DelegationCredit;
use App\Models\ias;
use App\Models\iefs;
use App\Models\VentilationDelegation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/**
 * Ecran FINPRONET frmEditEngDelegation.aspx - "Edition des engagements par delegation".
 *
 * L'etat restitue les lignes de ventilation d'un perimetre donne, une ligne par
 * numero de carton, avec le montant delegue, le montant engage et le reste a
 * engager. Meme source que l'ecran Ventilations (frmDetailDelegation.aspx), vue
 * en transversal sur plusieurs delegations au lieu d'une seule.
 *
 * Filtres de l'ecran d'origine : annee academique, periode de paie,
 * corps d'enseignant, IA, IEF, numero de carton, et type d'edition
 * (etat sur salaire / etat sur prime scolaire).
 */
class EditionEngagementController extends Controller
{
    private const PER_PAGE_DEFAUT = 50;

    private const RELATIONS = [
        'delegationCredit', 'corpsEnseignant', 'ia', 'ief',
        'centreExecution', 'budget', 'activite',
    ];

    /** Alimente les listes deroulantes de l'ecran. */
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

            'corps_enseignants' => corps_enseignants::orderBy('libelle')->get(['id', 'libelle']),

            'ias' => ias::orderBy('code')->get(['id', 'code', 'libelle']),

            'types' => [
                ['value' => VentilationDelegation::TYPE_SALAIRE, 'label' => 'Etat sur salaire'],
                ['value' => VentilationDelegation::TYPE_PRIME_SCOLAIRE, 'label' => 'Etat sur prime scolaire'],
            ],
        ]);
    }

    /** Cascade IA -> IEF, comme la saisie en cascade de FINPRONET. */
    public function iefsByIa($iaId)
    {
        return response()->json(
            iefs::where('ia_id', $iaId)
                ->orderBy('libelle')
                ->get(['id', 'code', 'libelle'])
        );
    }

    public function index(Request $request)
    {
        $v = $request->validate([
            'annee_academique'    => 'nullable|string|max:20',
            'periode_paie'        => 'nullable|string|max:50',
            'corps_enseignant_id' => 'nullable|exists:corps_enseignants,id',
            'ia_id'               => 'nullable|exists:ias,id',
            'ief_id'              => 'nullable|exists:iefs,id',
            'numero_carton'       => 'nullable|string|max:50',
            'type'                => 'nullable|in:salaire,prime_scolaire',
            'per_page'            => 'nullable|integer|min:1|max:1000',
        ]);

        $perPage = (int) ($v['per_page'] ?? self::PER_PAGE_DEFAUT);

        // Les agregats portent sur l'integralite du perimetre filtre, pas sur la
        // page affichee : le pied d'etat doit rester juste quel que soit le
        // decoupage en pages.
        $agregats = $this->requeteFiltree($v)
            ->selectRaw('COUNT(*) as nombre_lignes')
            ->selectRaw('COUNT(DISTINCT delegation_credit_id) as nombre_delegations')
            ->selectRaw('COALESCE(SUM(montant), 0) as total_montant')
            ->selectRaw('COALESCE(SUM(montant_engagement), 0) as total_engagement')
            ->first();

        $totalMontant    = (float) $agregats->total_montant;
        $totalEngagement = (float) $agregats->total_engagement;

        $page = $this->requeteFiltree($v)
            ->with(self::RELATIONS)
            ->orderBy('numero_carton')
            ->paginate($perPage)
            ->withQueryString();

        return response()->json([
            'filtres' => [
                'annee_academique'    => $v['annee_academique'] ?? null,
                'periode_paie'        => $v['periode_paie'] ?? null,
                'corps_enseignant_id' => $v['corps_enseignant_id'] ?? null,
                'ia_id'               => $v['ia_id'] ?? null,
                'ief_id'              => $v['ief_id'] ?? null,
                'numero_carton'       => $v['numero_carton'] ?? null,
                'type'                => $v['type'] ?? null,
            ],

            'lignes' => collect($page->items())
                ->map(fn (VentilationDelegation $x) => $this->ligne($x))
                ->values(),

            'pagination' => [
                'current_page' => $page->currentPage(),
                'per_page'     => $page->perPage(),
                'total'        => $page->total(),
                'last_page'    => $page->lastPage(),
            ],

            'recapitulatif' => $this->recapitulatifParDelegation($v),

            'totaux' => [
                'nombre_lignes'      => (int) $agregats->nombre_lignes,
                'nombre_delegations' => (int) $agregats->nombre_delegations,
                'total_montant'      => round($totalMontant, 2),
                'total_engagement'   => round($totalEngagement, 2),
                'total_reste'        => round($totalMontant - $totalEngagement, 2),
                'taux_engagement'    => $this->taux($totalEngagement, $totalMontant),
            ],
        ]);
    }

    /**
     * Sous-total par delegation, equivalent des ruptures d'etat de FINPRONET.
     * Agrege en base, puis rattache l'en-tete de chaque delegation.
     */
    private function recapitulatifParDelegation(array $v)
    {
        $groupes = $this->requeteFiltree($v)
            ->selectRaw('delegation_credit_id')
            ->selectRaw('COUNT(*) as nombre_lignes')
            ->selectRaw('COALESCE(SUM(montant), 0) as total_montant')
            ->selectRaw('COALESCE(SUM(montant_engagement), 0) as total_engagement')
            ->groupBy('delegation_credit_id')
            ->get();

        $delegations = DelegationCredit::whereIn('id', $groupes->pluck('delegation_credit_id'))
            ->get(['id', 'reference', 'objet', 'annee_academique', 'periode_paie'])
            ->keyBy('id');

        return $groupes->map(function ($g) use ($delegations) {
            $delegation = $delegations->get($g->delegation_credit_id);
            $montant    = (float) $g->total_montant;
            $engagement = (float) $g->total_engagement;

            return [
                'delegation_id'    => $g->delegation_credit_id,
                'reference'        => $delegation?->reference,
                'objet'            => $delegation?->objet,
                'annee_academique' => $delegation?->annee_academique,
                'periode_paie'     => $delegation?->periode_paie,
                'nombre_lignes'    => (int) $g->nombre_lignes,
                'total_montant'    => round($montant, 2),
                'total_engagement' => round($engagement, 2),
                'total_reste'      => round($montant - $engagement, 2),
                'taux_engagement'  => $this->taux($engagement, $montant),
            ];
        })->sortBy('reference')->values();
    }

    /** Perimetre commun aux agregats, au recapitulatif et a la page de lignes. */
    private function requeteFiltree(array $v): Builder
    {
        return VentilationDelegation::query()
            ->when($v['corps_enseignant_id'] ?? null, fn ($q, $id) => $q->where('corps_enseignant_id', $id))
            ->when($v['ia_id'] ?? null, fn ($q, $id) => $q->where('ia_id', $id))
            ->when($v['ief_id'] ?? null, fn ($q, $id) => $q->where('ief_id', $id))
            ->when($v['type'] ?? null, fn ($q, $type) => $q->where('type', $type))
            ->when(
                $v['numero_carton'] ?? null,
                fn ($q, $carton) => $q->where('numero_carton', 'like', '%'.$carton.'%')
            )
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
    }

    /** Une ligne d'etat, calquee sur la grille de frmDetailDelegation.aspx. */
    private function ligne(VentilationDelegation $x): array
    {
        $montant    = (float) $x->montant;
        $engagement = (float) $x->montant_engagement;

        return [
            'ventilation_id'        => $x->id,
            'delegation_id'         => $x->delegation_credit_id,
            'reference'             => $x->delegationCredit?->reference,
            'objet'                 => $x->delegationCredit?->objet,
            'annee_academique'      => $x->delegationCredit?->annee_academique,
            'periode_paie'          => $x->delegationCredit?->periode_paie,

            'numero_carton'         => $x->numero_carton,
            'numero_autorisation'   => $x->numero_autorisation,

            'corps_enseignant'      => $x->corpsEnseignant?->libelle,
            'ia'                    => $x->ia?->libelle,
            'ief'                   => $x->ief?->libelle,

            'centre_execution'      => $x->centreExecution?->code,
            'budget'                => $x->budget?->code,
            'activite'              => $x->activite?->code,
            'imputation_budgetaire' => $x->imputation_budgetaire,

            'montant'               => round($montant, 2),
            'engagement'            => round($engagement, 2),
            'reste'                 => round($montant - $engagement, 2),
            'taux_engagement'       => $this->taux($engagement, $montant),

            'type'                  => $x->type,
        ];
    }

    private function taux(float $engagement, float $montant): float
    {
        return $montant > 0 ? round($engagement / $montant * 100, 2) : 0.0;
    }
}
