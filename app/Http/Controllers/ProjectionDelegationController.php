<?php

namespace App\Http\Controllers;

use App\Models\corps_enseignants;
use App\Models\DelegationCredit;
use App\Models\VentilationDelegation;
use Illuminate\Http\Request;

/**
 * Ecran FINPRONET frmEditExpressionDel.aspx — "Mes projections".
 *
 * Projette le besoin en credits sur N mois a partir des credits reellement
 * engages sur une periode de reference, en appliquant un taux de majoration.
 *
 * FORMULE APPLIQUEE (a faire valider par le metier) :
 *
 *   base            = somme des engagements des ventilations de l'annee et de la
 *                     periode de reference, pour le corps d'enseignant choisi,
 *                     hors IA Dakar si l'option est cochee
 *   base_ajustee    = base - salaire_sans_retenue_tabaski + avance_tabaski
 *   mensuel_majore  = base_ajustee x (1 + taux_majoration / 100)
 *   projection      = mensuel_majore x nombre_de_mois
 *
 * Chaque etape est renvoyee separement pour que le calcul reste verifiable.
 */
class ProjectionDelegationController extends Controller
{
    public function filtres()
    {
        $annees = DelegationCredit::query()
            ->distinct()
            ->orderBy('annee_academique')
            ->pluck('annee_academique');

        $periodes = DelegationCredit::query()
            ->whereNotNull('periode_paie')
            ->distinct()
            ->orderBy('periode_paie')
            ->pluck('periode_paie');

        return response()->json([
            'annees_academiques' => $annees,
            'periodes'           => $periodes,
            'corps_enseignants'  => corps_enseignants::orderBy('libelle')->get(['id', 'libelle']),
        ]);
    }

    public function index(Request $request)
    {
        $v = $request->validate([
            'annee_academique'    => 'nullable|string|max:20',
            'annee_reference'     => 'nullable|string|max:20',
            'periode_reference'   => 'nullable|string|max:50',
            'corps_enseignant_id' => 'nullable|exists:corps_enseignants,id',
            'taux_majoration'     => 'nullable|numeric|min:0|max:100',
            'nombre_mois'         => 'required|integer|min:1|max:60',
            'avance_tabaski'      => 'nullable|numeric|min:0',
            'sans_retenue_tabaski'=> 'nullable|numeric|min:0',
            'exclure_dakar'       => 'nullable|boolean',
        ]);

        $taux        = (float) ($v['taux_majoration'] ?? 0);
        $mois        = (int) $v['nombre_mois'];
        $avance      = (float) ($v['avance_tabaski'] ?? 0);
        $sansRetenue = (float) ($v['sans_retenue_tabaski'] ?? 0);
        $horsDakar   = (bool) ($v['exclure_dakar'] ?? false);

        $query = VentilationDelegation::query()
            ->with(['ia', 'corpsEnseignant'])
            ->when($v['corps_enseignant_id'] ?? null, fn ($q, $id) => $q->where('corps_enseignant_id', $id))
            ->when($horsDakar, function ($q) {
                $q->where(function ($sub) {
                    $sub->whereNull('ia_id')
                        ->orWhereHas('ia', fn ($ia) => $ia->where('libelle', 'not like', '%Dakar%'));
                });
            })
            ->whereHas('delegationCredit', function ($q) use ($v) {
                if (! empty($v['annee_reference'])) {
                    $q->where('annee_academique', $v['annee_reference']);
                }
                if (! empty($v['periode_reference'])) {
                    $q->where('periode_paie', $v['periode_reference']);
                }
            });

        $ventilations = $query->get();

        $base          = (float) $ventilations->sum('montant_engagement');
        $baseAjustee   = $base - $sansRetenue + $avance;
        $mensuelMajore = $baseAjustee * (1 + $taux / 100);
        $projection    = $mensuelMajore * $mois;

        // Detail par IA, pour situer d'ou vient la base
        $parIa = $ventilations
            ->groupBy(fn (VentilationDelegation $x) => $x->ia?->libelle ?? 'Non rattachée')
            ->map(fn ($groupe, $libelle) => [
                'ia'            => $libelle,
                'nombre_lignes' => $groupe->count(),
                'engagement'    => round($groupe->sum('montant_engagement'), 2),
            ])
            ->sortByDesc('engagement')
            ->values();

        // Echeancier mois par mois
        $echeancier = collect(range(1, $mois))->map(fn ($n) => [
            'mois'   => $n,
            'montant'=> round($mensuelMajore, 2),
            'cumul'  => round($mensuelMajore * $n, 2),
        ]);

        return response()->json([
            'parametres' => [
                'annee_academique'     => $v['annee_academique'] ?? null,
                'annee_reference'      => $v['annee_reference'] ?? null,
                'periode_reference'    => $v['periode_reference'] ?? null,
                'corps_enseignant_id'  => $v['corps_enseignant_id'] ?? null,
                'taux_majoration'      => $taux,
                'nombre_mois'          => $mois,
                'avance_tabaski'       => $avance,
                'sans_retenue_tabaski' => $sansRetenue,
                'exclure_dakar'        => $horsDakar,
            ],
            'calcul' => [
                'base_reference'       => round($base, 2),
                'moins_sans_retenue'   => round($sansRetenue, 2),
                'plus_avance_tabaski'  => round($avance, 2),
                'base_ajustee'         => round($baseAjustee, 2),
                'taux_applique'        => $taux,
                'mensuel_majore'       => round($mensuelMajore, 2),
                'nombre_mois'          => $mois,
                'projection_totale'    => round($projection, 2),
            ],
            'source' => [
                'nombre_ventilations' => $ventilations->count(),
                'par_ia'              => $parIa,
            ],
            'echeancier' => $echeancier,
        ]);
    }
}
