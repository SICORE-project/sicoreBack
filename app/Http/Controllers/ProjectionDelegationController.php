<?php

namespace App\Http\Controllers;

use App\Models\bultins;
use App\Models\corps_enseignants;
use App\Models\DelegationCredit;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Ecran FINPRONET frmEditExpressionDel.aspx — "Mes projections".
 *
 * Projette le besoin en credits sur N mois a partir du COUT REEL DE LA PAIE
 * d'une periode de reference, en appliquant un taux de majoration.
 *
 * BASE DE CALCUL (arbitree par le metier) :
 *
 *   base = salaire brut de la periode de reference
 *        + charges patronales (brut x TAUX_CHARGES_EMPLOYEUR)
 *
 * C'est le cout budgetaire reel, donc le besoin en credits a deleguer — et non
 * les credits deja engages, qui etaient la base de la version precedente et ne
 * correspondaient pas a FINPRONET.
 *
 * FORMULE :
 *
 *   base_ajustee    = base - retenue_tabaski_neutralisee + avance_tabaski
 *   mensuel_majore  = base_ajustee x (1 + taux_majoration / 100)
 *   projection      = mensuel_majore x nombre_de_mois
 *
 * Chaque etape est renvoyee separement pour que le calcul reste verifiable.
 *
 * LIMITE CONNUE — options tabaski :
 * le metier attend un calcul automatique (le moteur retrouve les montants
 * d'avance et de retenue tabaski de la periode, le champ numerique servant de
 * plafond ou de correctif). Or aucune donnee tabaski n'existe dans cette base :
 * ni table dediee, ni rubrique de paie correspondante — rubrique_bultins ne
 * contient que IPRES, impot sur le revenu et CSS. La resolution automatique est
 * donc en place mais renvoie 0 tant que la source n'existe pas, et le montant
 * saisi sert de correctif. La reponse le signale explicitement dans
 * 'avertissements' plutot que de laisser croire a un calcul complet.
 */
class ProjectionDelegationController extends Controller
{
    /**
     * Taux de charges patronales.
     * A remonter dans les Parametres generaux : c'est un parametre de gestion.
     */
    private const TAUX_CHARGES_EMPLOYEUR = 0.16;

    public function filtres()
    {
        return response()->json([
            'annees_academiques' => DelegationCredit::query()
                ->distinct()
                ->orderBy('annee_academique')
                ->pluck('annee_academique'),

            // La base est desormais la paie : les periodes de reference sont
            // donc celles des bulletins, pas celles des delegations.
            'periodes' => bultins::query()
                ->select(DB::raw("DATE_FORMAT(mois_validite, '%Y-%m') as periode"))
                ->distinct()
                ->orderBy('periode')
                ->pluck('periode'),

            'corps_enseignants' => corps_enseignants::orderBy('libelle')->get(['id', 'libelle']),
        ]);
    }

    public function index(Request $request)
    {
        $v = $request->validate([
            'annee_academique'     => 'nullable|string|max:20',
            'annee_reference'      => 'nullable|string|max:20',
            'periode_reference'    => 'nullable|string|max:50',
            'corps_enseignant_id'  => 'nullable|exists:corps_enseignants,id',
            'taux_majoration'      => 'nullable|numeric|min:0|max:100',
            'nombre_mois'          => 'required|integer|min:1|max:60',
            'avance_tabaski'       => 'nullable|numeric|min:0',
            'sans_retenue_tabaski' => 'nullable|numeric|min:0',
            'exclure_dakar'        => 'nullable|boolean',
        ]);

        $taux      = (float) ($v['taux_majoration'] ?? 0);
        $mois      = (int) $v['nombre_mois'];
        $horsDakar = (bool) ($v['exclure_dakar'] ?? false);

        $requete = $this->requetePaie($v, $horsDakar);

        $agregats = (clone $requete)
            ->selectRaw('COUNT(*) as nombre_bulletins')
            ->selectRaw('COUNT(DISTINCT bultins.enseignant_id) as nombre_agents')
            ->selectRaw('COALESCE(SUM(bultins.net_a_payer), 0) as total_net')
            ->first();

        // Le brut et les retenues sont pris dans detail_bultins, pas dans les
        // colonnes bultins.salaire_brut / total_retenues : ces colonnes existent
        // mais ne sont pas alimentees (toutes a 0 en base), alors que le detail
        // par rubrique, lui, l'est.
        $lignes = $this->agregatDetails($requete);

        $brut    = (float) $lignes['gains'];
        $charges = $brut * self::TAUX_CHARGES_EMPLOYEUR;
        $base    = $brut + $charges;

        // Resolution automatique des montants tabaski sur le perimetre retenu.
        // Le montant saisi sert de correctif quand la case est cochee.
        $avance      = $this->resoudreTabaski('avance', $requete, $v['avance_tabaski'] ?? null);
        $sansRetenue = $this->resoudreTabaski('retenue', $requete, $v['sans_retenue_tabaski'] ?? null);

        $baseAjustee   = $base - $sansRetenue + $avance;
        $mensuelMajore = $baseAjustee * (1 + $taux / 100);
        $projection    = $mensuelMajore * $mois;

        // Detail par IA, pour situer d'ou vient la base
        $parIa = (clone $requete)
            ->leftJoin('ias', 'ias.id', '=', 'bultins.ia_id')
            ->leftJoin('detail_bultins', 'detail_bultins.bultin_id', '=', 'bultins.id')
            ->selectRaw('COALESCE(ias.libelle, ?) as ia', ['Non rattachée'])
            ->selectRaw('COUNT(DISTINCT bultins.id) as nombre_bulletins')
            ->selectRaw('COALESCE(SUM(detail_bultins.montant_gains), 0) as brut')
            ->groupBy('ia')
            ->orderByDesc('brut')
            ->get()
            ->map(fn ($r) => [
                'ia'                => $r->ia,
                'nombre_bulletins'  => (int) $r->nombre_bulletins,
                'brut'              => round((float) $r->brut, 2),
                'cout_charge'       => round((float) $r->brut * (1 + self::TAUX_CHARGES_EMPLOYEUR), 2),
            ]);

        $echeancier = collect(range(1, $mois))->map(fn ($n) => [
            'mois'    => $n,
            'montant' => round($mensuelMajore, 2),
            'cumul'   => round($mensuelMajore * $n, 2),
        ]);

        return response()->json([
            'parametres' => [
                'annee_academique'     => $v['annee_academique'] ?? null,
                'annee_reference'      => $v['annee_reference'] ?? null,
                'periode_reference'    => $v['periode_reference'] ?? null,
                'corps_enseignant_id'  => $v['corps_enseignant_id'] ?? null,
                'taux_majoration'      => $taux,
                'nombre_mois'          => $mois,
                'avance_tabaski'       => round($avance, 2),
                'sans_retenue_tabaski' => round($sansRetenue, 2),
                'exclure_dakar'        => $horsDakar,
                'taux_charges'         => self::TAUX_CHARGES_EMPLOYEUR * 100,
            ],

            'calcul' => [
                'salaire_brut'         => round($brut, 2),
                'charges_employeur'    => round($charges, 2),
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
                'nombre_bulletins' => (int) $agregats->nombre_bulletins,
                'nombre_agents'    => (int) $agregats->nombre_agents,
                'total_retenues'   => round((float) $lignes['retenues'], 2),
                'total_net'        => round((float) $agregats->total_net, 2),
                'par_ia'           => $parIa,
            ],

            'echeancier'     => $echeancier,
            'avertissements' => $this->avertissements($v, $agregats),
        ]);
    }

    /**
     * Perimetre de paie servant de base : periode de reference, corps
     * d'enseignant, et exclusion eventuelle de Dakar.
     *
     * Le corps n'est pas porte par le bulletin : on passe par l'enseignant.
     */
    private function requetePaie(array $v, bool $horsDakar): Builder
    {
        return bultins::query()
            ->when(
                $v['periode_reference'] ?? null,
                fn ($q, $periode) => $q->whereRaw("DATE_FORMAT(bultins.mois_validite, '%Y-%m') = ?", [$periode])
            )
            ->when(
                $v['corps_enseignant_id'] ?? null,
                fn ($q, $id) => $q->whereHas('enseignant', fn ($e) => $e->where('corps_enseignant_id', $id))
            )
            ->when($horsDakar, fn ($q) => $q->where(function ($sub) {
                $sub->whereNull('bultins.ia_id')
                    ->orWhereHas('ia', fn ($ia) => $ia->where('libelle', 'not like', '%Dakar%'));
            }));
    }

    /**
     * Cumul des gains et des retenues du perimetre, lu dans detail_bultins.
     * Requete separee plutot qu'une jointure sur la requete principale, pour
     * ne pas dupliquer les lignes de bulletin dans les autres agregats.
     */
    private function agregatDetails(Builder $perimetre): array
    {
        $r = DB::table('detail_bultins')
            ->whereIn('bultin_id', (clone $perimetre)->select('bultins.id'))
            ->selectRaw('COALESCE(SUM(montant_gains), 0) as gains')
            ->selectRaw('COALESCE(SUM(montant_retenus), 0) as retenues')
            ->first();

        return [
            'gains'    => (float) ($r->gains ?? 0),
            'retenues' => (float) ($r->retenues ?? 0),
        ];
    }

    /**
     * Montant tabaski a appliquer sur le perimetre.
     *
     * Le metier attend une resolution automatique. Aucune source n'existe
     * aujourd'hui dans cette base (ni table tabaski, ni rubrique de paie
     * correspondante), la resolution renvoie donc 0 et l'on retombe sur le
     * montant saisi, qui joue le role de correctif manuel.
     */
    private function resoudreTabaski(string $nature, Builder $perimetre, $montantSaisi): float
    {
        $automatique = $this->montantTabaskiEnBase($nature, $perimetre);

        if ($automatique !== null) {
            // Le montant saisi plafonne le montant retrouve.
            return $montantSaisi !== null
                ? min($automatique, (float) $montantSaisi)
                : $automatique;
        }

        return (float) ($montantSaisi ?? 0);
    }

    /**
     * Retrouve le cumul tabaski dans les rubriques de paie du perimetre.
     * Renvoie null tant qu'aucune rubrique tabaski n'existe : c'est ce qui
     * permet de distinguer "pas de source" de "source a zero".
     */
    private function montantTabaskiEnBase(string $nature, Builder $perimetre): ?float
    {
        $motif = $nature === 'avance' ? '%avance%tabaski%' : '%retenue%tabaski%';

        $rubriques = DB::table('rubrique_bultins')
            ->where('libelle', 'like', $motif)
            ->orWhere('libelle', 'like', '%tabaski%')
            ->pluck('id');

        if ($rubriques->isEmpty()) {
            return null;
        }

        $colonne = $nature === 'avance' ? 'montant_gains' : 'montant_retenus';

        return (float) DB::table('detail_bultins')
            ->whereIn('rubrique_bultin_id', $rubriques)
            ->whereIn('bultin_id', (clone $perimetre)->select('bultins.id'))
            ->sum($colonne);
    }

    /** Conditions dans lesquelles la projection n'est pas interpretable. */
    private function avertissements(array $v, $agregats): array
    {
        $messages = [];

        if ((int) $agregats->nombre_bulletins === 0) {
            $messages[] = "Aucun bulletin sur ce périmètre : la base de référence est nulle, "
                . "la projection l'est aussi.";
        }

        if (empty($v['periode_reference'])) {
            $messages[] = "Aucune période de référence sélectionnée : la base cumule tous les "
                . "bulletins disponibles, pas un seul mois.";
        }

        if ($this->montantTabaskiEnBase('avance', bultins::query()) === null) {
            $messages[] = "Aucune rubrique tabaski n'existe en base : les montants tabaski ne "
                . "sont pas calculés automatiquement, seuls les montants saisis sont appliqués.";
        }

        $messages[] = "Charges patronales appliquées au taux de "
            . (self::TAUX_CHARGES_EMPLOYEUR * 100) . " %, à confirmer dans les Paramètres généraux.";

        return $messages;
    }
}
