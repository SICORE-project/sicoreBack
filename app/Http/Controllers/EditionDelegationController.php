<?php

namespace App\Http\Controllers;

use App\Models\corps_enseignants;
use App\Models\DelegationCredit;
use App\Models\ias;
use App\Models\iefs;
use App\Models\VentilationDelegation;
use Illuminate\Http\Request;

/**
 * Ecran FINPRONET frmEditDetailDelegation.aspx — "Edition des delegations de credits".
 * Periode comptable (annee, corps, IA, IEF, periode du/au) + type d'edition
 * salaire ou prime scolaire, puis restitution d'un etat imprimable.
 */
class EditionDelegationController extends Controller
{
    /** Alimente les listes deroulantes de l'ecran. */
    public function filtres()
    {
        return response()->json([
            'annees_academiques' => DelegationCredit::query()
                ->distinct()
                ->orderBy('annee_academique')
                ->pluck('annee_academique'),
            'corps_enseignants' => corps_enseignants::orderBy('libelle')->get(['id', 'libelle']),
            'ias' => ias::orderBy('libelle')->get(['id', 'code', 'libelle']),
            'iefs' => iefs::orderBy('libelle')->get(['id', 'code', 'libelle', 'ia_id']),
            'types' => [
                ['value' => VentilationDelegation::TYPE_SALAIRE, 'label' => 'Etat sur salaire'],
                ['value' => VentilationDelegation::TYPE_PRIME_SCOLAIRE, 'label' => 'Etat sur prime scolaire'],
            ],
        ]);
    }

    public function index(Request $request)
    {
        $validated = $request->validate([
            'annee_academique'    => 'nullable|string|max:20',
            'corps_enseignant_id' => 'nullable|exists:corps_enseignants,id',
            'ia_id'               => 'nullable|exists:ias,id',
            'ief_id'              => 'nullable|exists:iefs,id',
            'date_debut'          => 'nullable|date',
            'date_fin'            => 'nullable|date|after_or_equal:date_debut',
            'type'                => 'nullable|in:salaire,prime_scolaire',
        ]);

        $ventilations = VentilationDelegation::query()
            ->with(['delegationCredit', 'corpsEnseignant', 'ia', 'ief', 'centreExecution', 'budget', 'activite'])
            ->when($validated['corps_enseignant_id'] ?? null, fn ($q, $v) => $q->where('corps_enseignant_id', $v))
            ->when($validated['ia_id'] ?? null, fn ($q, $v) => $q->where('ia_id', $v))
            ->when($validated['ief_id'] ?? null, fn ($q, $v) => $q->where('ief_id', $v))
            ->when($validated['type'] ?? null, fn ($q, $v) => $q->where('type', $v))
            ->whereHas('delegationCredit', function ($q) use ($validated) {
                if (! empty($validated['annee_academique'])) {
                    $q->where('annee_academique', $validated['annee_academique']);
                }
                if (! empty($validated['date_debut'])) {
                    $q->whereDate('date_delegation', '>=', $validated['date_debut']);
                }
                if (! empty($validated['date_fin'])) {
                    $q->whereDate('date_delegation', '<=', $validated['date_fin']);
                }
            })
            ->get();

        $lignes = $ventilations->map(function (VentilationDelegation $v) {
            $montant = (float) $v->montant;
            $engagement = (float) $v->montant_engagement;

            return [
                'ventilation_id'        => $v->id,
                'delegation_id'         => $v->delegation_credit_id,
                'reference'             => $v->delegationCredit?->reference,
                'objet'                 => $v->delegationCredit?->objet,
                'annee_academique'      => $v->delegationCredit?->annee_academique,
                'periode_paie'          => $v->delegationCredit?->periode_paie,
                'date_delegation'       => $v->delegationCredit?->date_delegation?->toDateString(),
                'corps_enseignant'      => $v->corpsEnseignant?->libelle ?? '-',
                'ia'                    => $v->ia?->libelle ?? '-',
                'ief'                   => $v->ief?->libelle ?? '-',
                'centre_execution'      => $v->centreExecution?->code,
                'budget'                => $v->budget?->code,
                'activite'              => $v->activite?->code,
                'imputation_budgetaire' => $v->imputation_budgetaire,
                'numero_autorisation'   => $v->numero_autorisation,
                'numero_carton'         => $v->numero_carton,
                'montant'               => round($montant, 2),
                'engagement'            => round($engagement, 2),
                'reste'                 => round($montant - $engagement, 2),
                'type'                  => $v->type,
            ];
        })->sortBy('numero_carton')->values();

        // Recapitulatif par delegation, comme le pied d'etat de FINPRONET
        $recapitulatif = $lignes->groupBy('delegation_id')->map(function ($groupe) {
            $premiere = $groupe->first();

            return [
                'delegation_id'   => $premiere['delegation_id'],
                'reference'       => $premiere['reference'],
                'objet'           => $premiere['objet'],
                'periode_paie'    => $premiere['periode_paie'],
                'nombre_lignes'   => $groupe->count(),
                'total_montant'   => round($groupe->sum('montant'), 2),
                'total_engagement'=> round($groupe->sum('engagement'), 2),
                'total_reste'     => round($groupe->sum('reste'), 2),
            ];
        })->values();

        return response()->json([
            'filtres' => [
                'annee_academique'    => $validated['annee_academique'] ?? null,
                'corps_enseignant_id' => $validated['corps_enseignant_id'] ?? null,
                'ia_id'               => $validated['ia_id'] ?? null,
                'ief_id'              => $validated['ief_id'] ?? null,
                'date_debut'          => $validated['date_debut'] ?? null,
                'date_fin'            => $validated['date_fin'] ?? null,
                'type'                => $validated['type'] ?? null,
            ],
            'lignes' => $lignes,
            'recapitulatif' => $recapitulatif,
            'totaux' => [
                'nombre_delegations' => $recapitulatif->count(),
                'nombre_lignes'      => $lignes->count(),
                'total_montant'      => round($lignes->sum('montant'), 2),
                'total_engagement'   => round($lignes->sum('engagement'), 2),
                'total_reste'        => round($lignes->sum('reste'), 2),
            ],
        ]);
    }
}
