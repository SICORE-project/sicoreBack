<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Reproduit a l'identique la delegation visible dans FINPRONET
 * (frmDelegation.aspx + frmDetailDelegation.aspx) pour servir de reference
 * de recette lors de la bascule SICORE.
 */
class DelegationFinpronetSeeder extends Seeder
{
    public function run(): void
    {
        // --- Nomenclature budgetaire ---
        $centreZiguinchor = $this->refId('centres_execution', 'D02421361510211', 'IA ZIGUICHOR');
        $budgetPC = $this->refId('budgets', 'BF36238', 'PROFESSEURS CONTRACTUELS');
        $activiteSalaire = $this->refId('activites', '03P233010102', 'Salaire du personnel');

        // --- Corps d'enseignant FINPRONET ---
        $corpsPC = DB::table('corps_enseignants')->where('libelle', 'PC PROFESSEURS CONTRACTUELS')->value('id');
        if (! $corpsPC) {
            $corpsPC = DB::table('corps_enseignants')->insertGetId([
                'libelle' => 'PC PROFESSEURS CONTRACTUELS',
                'categorie_id' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $iaZiguinchor = DB::table('ias')->where('code', 'IA-ZG')->value('id');

        // --- En-tete de la delegation (onglet Saisie) ---
        $montantTotal = 424518628; // somme des 10 cartons ci-dessous

        $delegationId = DB::table('delegation_credits')->where('reference', 'FP-15/10/2025')->value('id');

        if (! $delegationId) {
            $delegationId = DB::table('delegation_credits')->insertGetId([
                'annee_academique'   => '2025/2026',
                'periode_paie'       => 'octobre',
                'reference'          => 'FP-15/10/2025',
                'objet'              => 'SALAIRE PC',
                'date_delegation'    => '2025-10-15',
                'date_fin'           => null,
                'structure_id'       => null,
                'service_id'         => null,
                'montant_initial'    => $montantTotal,
                'montant_disponible' => $montantTotal,
                'montant_engage'     => 0,
                'montant_consomme'   => 0,
                'solde'              => $montantTotal,
                'statut'             => 'Validée',
                'created_at'         => now(),
                'updated_at'         => now(),
            ]);
        }

        // --- Ventilations : grille des cartons de frmDetailDelegation.aspx ---
        // [N° Carton, Montant, Engagement]
        $cartons = [
            ['25-DC1823', 61550685, 61492641],
            ['25-DC1824', 37731570, 37731230],
            ['25-DC1825', 57600588, 57512518],
            ['25-DC1826', 43331963, 43306306],
            ['25-DC1827', 29245881, 29243376],
            ['25-DC1828', 88612486, 88610543],
            ['25-DC1829', 30558258, 30458708],
            ['25-DC1830',  8268908,  8161315],
            ['25-DC1831', 33577317, 33575264],
            ['25-DC1832', 34040972, 29350106],
        ];

        foreach ($cartons as [$carton, $montant, $engagement]) {
            $existe = DB::table('ventilations_delegation')
                ->where('delegation_credit_id', $delegationId)
                ->where('numero_carton', $carton)
                ->exists();

            if ($existe) {
                continue;
            }

            DB::table('ventilations_delegation')->insert([
                'delegation_credit_id'  => $delegationId,
                'corps_enseignant_id'   => $corpsPC,
                'ia_id'                 => $iaZiguinchor,
                'ief_id'                => null,
                'centre_execution_id'   => $centreZiguinchor,
                'budget_id'             => $budgetPC,
                'activite_id'           => $activiteSalaire,
                'imputation_budgetaire' => '006213615102116238',
                'numero_autorisation'   => $carton,
                'numero_carton'         => $carton,
                'montant'               => $montant,
                'montant_engagement'    => $engagement,
                'type'                  => 'salaire',
                'created_at'            => now(),
                'updated_at'            => now(),
            ]);
        }
    }

    /** Cree la ligne de nomenclature si absente et renvoie son id. */
    private function refId(string $table, string $code, string $libelle): int
    {
        $id = DB::table($table)->where('code', $code)->value('id');

        if ($id) {
            return $id;
        }

        return DB::table($table)->insertGetId([
            'code' => $code,
            'libelle' => $libelle,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
