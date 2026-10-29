<?php

namespace App\Services\Paie\EtatSalaire;

use App\Models\Enseignant;
use App\Models\PayrollElement;
use App\Models\PayrollPayslip;
use App\Models\PayrollPeriod;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * État mensuel des salaires (slug paie-etat-salaires).
 *
 * Utilisé par App\Services\PayrollPageService : le trait s'appuie sur les
 * helpers communs du service (payslips, scopePayroll, stat, money, etc.)
 * et sur la portée IA ($iaScopeId).
 */
trait EtatSalaireReport
{
    /** @return array<string, mixed> */
    private function salaryReport(?PayrollPeriod $period, array $criteria = []): array
    {
        $items = $this->payslips($period);
        $academicYear = $this->academicYearForPeriod($period);
        $corps = $this->referenceMap('corps_enseignant', 'libelle');
        $categories = $this->referenceMap('categories', 'libelle');
        $statuts = $this->referenceMap('statuts_enseignant', 'libelle');
        $ias = $this->referenceMap('ias', 'libelle');
        $iefs = $this->referenceMap('iefs', 'libelle');
        $paymentPlaces = $this->referenceMap('lieux_paiement', 'libelle');
        $trainingCenters = $this->referenceMap('lieu_de_services', 'libelle');
        $banking = $this->salaryBankingDetails($items->pluck('enseignant_id'));
        $rubrics = $this->salaryStatementRubrics();
        $tabaskiAdvances = $period
            ? $this->scopePayroll(PayrollElement::query())
                ->where('payroll_period_id', $period->id)
                ->where('code', 'TABASKI_AVANCE')
                ->where('status', 'validated')
                ->where('is_exempt', false)
                ->get(['enseignant_id', 'amount'])
                ->groupBy('enseignant_id')
                ->map(fn (Collection $elements): float => (float) $elements->sum('amount'))
            : collect();

        if (
            filled($criteria['academic_year_id'] ?? null)
            && (int) ($academicYear->id ?? 0) !== (int) $criteria['academic_year_id']
        ) {
            $items = collect();
        }

        $items = $items->filter(function (PayrollPayslip $payslip) use ($criteria, $tabaskiAdvances): bool {
            $teacher = $payslip->enseignant;

            if (filled($criteria['statut_enseignant_id'] ?? null)
                && (int) $teacher->getAttribute('statut_enseignant_id') !== (int) $criteria['statut_enseignant_id']) {
                return false;
            }
            if (filled($criteria['ia_id'] ?? null)
                && $this->teacherIaId($teacher) !== (int) $criteria['ia_id']) {
                return false;
            }
            if (filled($criteria['ief_id'] ?? null)
                && $this->teacherIefId($teacher) !== (int) $criteria['ief_id']) {
                return false;
            }
            if (filled($criteria['payment_place_id'] ?? null)
                && (int) $teacher->getAttribute('lieu_paiement_id') !== (int) $criteria['payment_place_id']) {
                return false;
            }
            if (filled($criteria['training_center_id'] ?? null)
                && (int) $teacher->getAttribute('lieu_service_id') !== (int) $criteria['training_center_id']) {
                return false;
            }
            if (filled($criteria['matricule'] ?? null)
                && ! str_contains(
                    $this->normalizeMatricule($teacher->matricule),
                    $this->normalizeMatricule((string) $criteria['matricule'])
                )) {
                return false;
            }
            if (($criteria['tabaski_only'] ?? false)
                && $this->salaryLineAmount($payslip, ['TABASKI_AVANCE']) <= 0
                && (float) $tabaskiAdvances->get($teacher->id, 0) <= 0) {
                return false;
            }

            return true;
        })->values();

        $amountKeys = [
            'salary_category',
            'special_bonus',
            'taxable_amount',
            'compensation_increase',
            'ipres',
            'ipm',
            'impr',
            'trimf',
            'union_social',
            'tabaski_advance',
            'reminder',
            'salary_deduction',
            'reminder_deduction',
            'tabaski_deduction',
            'other_deductions',
            'gross',
            'deductions',
            'net',
            ...$rubrics->map(fn (object $rubric): string => 'rubric_'.$rubric->id)->all(),
        ];
        $recognizedDeductionCodes = [
            'IPRES_SALARIE', 'IPM', 'IMPR', 'TRIMF', 'CHECKOFF_UES', 'CHECKOFF',
            'OEUVRE_SOCIALE', 'TABASKI_RETENUE', 'RAPPEL_RETENUE', 'RETENUE_SUR_RAPPEL',
            'RETENUE_SALAIRE', 'R94', 'ABSENCE',
        ];

        $records = $items->map(function (PayrollPayslip $payslip, int $index) use (
            $banking,
            $corps,
            $categories,
            $ias,
            $iefs,
            $paymentPlaces,
            $trainingCenters,
            $tabaskiAdvances,
            $recognizedDeductionCodes,
            $rubrics
        ): array {
            $teacher = $payslip->enseignant;
            $bank = $banking->get($teacher->id, []);
            $salaryCategory = $this->salaryLineAmount($payslip, ['SALAIRE_BASE'])
                + (float) $payslip->lines->where('source', 'salary_increase')->sum('amount');
            $tabaskiAdvance = $this->salaryLineAmount($payslip, ['TABASKI_AVANCE']);
            if ($tabaskiAdvance <= 0) {
                $tabaskiAdvance = (float) $tabaskiAdvances->get($teacher->id, 0);
            }
            $taxableAmount = (float) $payslip->lines
                ->where('category', 'earning')
                ->reject(fn ($line): bool => $line->code === 'TABASKI_AVANCE')
                ->sum('amount');
            $otherDeductions = (float) $payslip->lines
                ->whereIn('category', ['deduction', 'contribution'])
                ->whereNotIn('code', $recognizedDeductionCodes)
                ->sum('amount');
            $profileCategory = data_get($payslip->profile_snapshot, 'category_level');
            $iaId = $this->teacherIaId($teacher);
            $iefId = $this->teacherIefId($teacher);

            // Montant paramétré dans rubrique_paies (et non la ligne du bulletin calculé).
            $rubricAmounts = $rubrics->mapWithKeys(fn (object $rubric): array => [
                'rubric_'.$rubric->id => (float) ($rubric->montant ?? 0),
            ])->all();

            return $rubricAmounts + [
                'sequence' => $index + 1,
                'first_name' => $this->teacherFirstName($teacher),
                'last_name' => $this->teacherLastName($teacher),
                'matricule' => $teacher->matricule ?: '—',
                'salary_category' => $salaryCategory,
                'special_bonus' => $this->salaryLineAmount($payslip, ['PRIME_SPECIALE']),
                'taxable_amount' => $taxableAmount,
                'compensation_increase' => $this->salaryLineAmount(
                    $payslip,
                    ['INDEMNITE_COMPENSATION', 'IRD', 'AUGMENTATION_IRD']
                ),
                'ipres' => $this->salaryLineAmount($payslip, ['IPRES_SALARIE']),
                'ipm' => $this->salaryLineAmount($payslip, ['IPM']),
                'impr' => $this->salaryLineAmount($payslip, ['IMPR']),
                'trimf' => $this->salaryLineAmount($payslip, ['TRIMF']),
                'union_social' => $this->salaryLineAmount(
                    $payslip,
                    ['CHECKOFF_UES', 'CHECKOFF', 'OEUVRE_SOCIALE']
                ),
                'tabaski_advance' => $tabaskiAdvance,
                'reminder' => $this->salaryLineAmount($payslip, ['RAPPEL', 'RAPPEL_GAIN']),
                'salary_deduction' => $this->salaryLineAmount(
                    $payslip,
                    ['RETENUE_SALAIRE', 'R94', 'ABSENCE']
                ),
                'reminder_deduction' => $this->salaryLineAmount(
                    $payslip,
                    ['RAPPEL_RETENUE', 'RETENUE_SUR_RAPPEL']
                ),
                'tabaski_deduction' => $this->salaryLineAmount($payslip, ['TABASKI_RETENUE']),
                'other_deductions' => $otherDeductions,
                'gross' => (float) $payslip->gross_amount,
                'deductions' => (float) $payslip->deduction_amount,
                'net' => (float) $payslip->net_amount,
                'cni' => $teacher->getAttribute('cni') ?: 'Non renseignée',
                'account' => $bank['account'] ?? $this->teacherAccountNumber($teacher),
                'parts' => $this->teacherTaxParts($teacher),
                'category' => $categories->get(
                    (int) $teacher->getAttribute('categorie_id'),
                    $profileCategory ? (string) $profileCategory : '—'
                ),
                'corps' => $corps->get(
                    (int) ($teacher->getAttribute('corps_id') ?? $teacher->getAttribute('corps_enseignant_id')),
                    '—'
                ),
                'ia' => $ias->get($iaId, 'IA non renseignée'),
                'ief' => $iefs->get($iefId, 'IEF non renseignée'),
                'training_center' => $trainingCenters->get(
                    (int) $teacher->getAttribute('lieu_service_id'),
                    'Non renseigné'
                ),
                'bank' => $bank['bank'] ?? ($teacher->getAttribute('code_banque') ?: 'Non renseignée'),
                'payment_place' => $paymentPlaces->get(
                    (int) $teacher->getAttribute('lieu_paiement_id'),
                    'Non renseigné'
                ),
                'payment_status' => $payslip->payment_status,
                'payment_status_label' => $this->statusLabel($payslip->payment_status),
                'payment_status_variant' => data_get($this->statusCell($payslip->payment_status), 'badge'),
                'reference' => $payslip->reference,
            ];
        });

        $withSignature = (bool) ($criteria['with_signature'] ?? false);
        $columns = $this->salaryStatementColumns($withSignature, $rubrics);
        $displayRows = $records->map(function (array $record) use ($columns, $amountKeys): array {
            return collect($columns)->map(function (array $column) use ($record, $amountKeys): string {
                $key = $column['key'];
                if ($key === 'signature') {
                    return '';
                }

                return in_array($key, $amountKeys, true)
                    ? $this->statementAmount($record[$key] ?? 0)
                    : (string) ($record[$key] ?? '—');
            })->values()->all();
        })->values();
        $totals = collect($amountKeys)->mapWithKeys(
            fn (string $key): array => [$key => (float) $records->sum($key)]
        );
        $gross = (float) $records->sum('gross');
        $deductions = (float) $records->sum('deductions');
        $net = (float) $records->sum('net');

        return [
            'columns' => collect($columns)->pluck('label')->values()->all(),
            'rows' => $displayRows,
            'actions' => [$this->exportAction()],
            'stats' => [
                $this->stat('Agents édités', $records->count(), $period?->label ?? 'Aucune période', 'EN', 'green'),
                $this->stat('Masse brute', $this->money($gross), 'Total des gains', 'BR', 'blue'),
                $this->stat('Retenues', $this->money($deductions), 'Sociales, fiscales et diverses', 'CS', 'yellow'),
                $this->stat('Net à payer', $this->money($net), 'État sélectionné', 'SP', 'red'),
            ],
            'notice' => $records->isEmpty()
                ? 'Aucun bulletin ne correspond aux critères sélectionnés pour cette période.'
                : 'État mensuel détaillé construit à partir des bulletins calculés et de leurs rubriques traçables.',
            'salary_statement' => [
                'title' => 'État des salaires',
                'heading' => $this->salaryStatementHeading($period, $criteria, $records, $ias, $statuts),
                'period_label' => $period?->label ?? 'Période non renseignée',
                'academic_year' => $academicYear->libelle ?? 'Non renseignée',
                'columns' => $columns,
                'rows' => $records->map(function (array $record) use ($amountKeys): array {
                    foreach ($amountKeys as $key) {
                        $record[$key.'_display'] = $this->statementAmount($record[$key] ?? 0);
                    }

                    return $record;
                })->values(),
                'totals' => $totals->map(fn (float $amount): string => $this->statementAmount($amount)),
                'filters' => $criteria,
                'filter_options' => $this->salaryStatementFilterOptions(
                    $items,
                    $statuts,
                    $ias,
                    $iefs,
                    $paymentPlaces,
                    $trainingCenters
                ),
                'with_signature' => $withSignature,
                'service_done' => ! (bool) ($criteria['without_service_done'] ?? false),
                'signatory' => (bool) ($criteria['dage_signatory'] ?? false)
                    ? 'Le Directeur de l’Administration générale et de l’Équipement (DAGE)'
                    : 'Le responsable habilité de la paie',
                'generated_at' => now()->format('d/m/Y à H:i'),
            ],
        ];
    }

    /** Ex. « ETAT DES SALAIRES OCTOBRE 2026 IA : IA DAKAR PROFESSEURS CONTRACTUELS ». */
    private function salaryStatementHeading(
        ?PayrollPeriod $period,
        array $criteria,
        Collection $records,
        Collection $ias,
        Collection $statuts
    ): string {
        $iaId = $criteria['ia_id'] ?? $this->iaScopeId;
        $iaNames = $records->pluck('ia')->unique();
        $ia = filled($iaId)
            ? $ias->get((int) $iaId, 'IA non renseignée')
            : ($iaNames->count() === 1 ? $iaNames->first() : 'TOUTES LES IA');

        $statut = $statuts->get((int) ($criteria['statut_enseignant_id'] ?? 0));
        $teachers = ($statut
            ? ' '.(preg_match('/[sx]$/iu', $statut) ? $statut : $statut.'s')
            : '');

        return mb_strtoupper(trim(
            'Etat des salaires '.($period?->label ?? '').' IA : '.$ia.' '.$teachers
        ));
    }

    /**
     * Colonnes de l'état : identité, salaire catégoriel, puis une colonne par
     * rubrique active de rubrique_paies (gains puis retenues), puis totaux.
     *
     * @return array<int, array{key: string, label: string, amount?: bool}>
     */
    private function salaryStatementColumns(bool $withSignature, Collection $rubrics): array
    {
        $columns = [
            ['key' => 'sequence', 'label' => 'N°'],
            ['key' => 'first_name', 'label' => 'Prénoms'],
            ['key' => 'last_name', 'label' => 'Nom'],
            ['key' => 'matricule', 'label' => 'Matricule'],
            ['key' => 'corps', 'label' => 'Corps'],
            ['key' => 'category', 'label' => 'Catégorie'],
            ['key' => 'salary_category', 'label' => 'Sal. catég.', 'amount' => true],
            ...$rubrics->map(fn (object $rubric): array => [
                'key' => 'rubric_'.$rubric->id,
                'label' => $rubric->libelle,
                'amount' => true,
            ])->all(),
            ['key' => 'gross', 'label' => 'Salaire brut', 'amount' => true],
            ['key' => 'deductions', 'label' => 'Total retenues', 'amount' => true],
            ['key' => 'net', 'label' => 'Net à payer', 'amount' => true],
            ['key' => 'cni', 'label' => 'CNI'],
            ['key' => 'account', 'label' => 'N° compte'],
            ['key' => 'parts', 'label' => 'Parts'],
            ['key' => 'bank', 'label' => 'Banque'],
            ['key' => 'payment_place', 'label' => 'Lieu de paiement'],
            ['key' => 'payment_status_label', 'label' => 'Paiement'],
        ];

        if ($withSignature) {
            $columns[] = ['key' => 'signature', 'label' => 'Émargement'];
        }

        return $columns;
    }

    /** Rubriques actives à afficher sur l'état, gains d'abord. */
    private function salaryStatementRubrics(): Collection
    {
        if (! Schema::hasTable('rubrique_paies')) {
            return collect();
        }

        return DB::table('rubrique_paies')
            ->where('est_actif', true)
            ->orderByRaw("CASE WHEN type = 'gain' THEN 0 ELSE 1 END")
            ->orderBy('id')
            ->get(['id', 'code', 'libelle', 'montant']);
    }

    /** @return array<string, mixed> */
    private function salaryStatementFilterOptions(
        Collection $items,
        Collection $statuts,
        Collection $ias,
        Collection $iefs,
        Collection $paymentPlaces,
        Collection $trainingCenters
    ): array {
        $iefIa = Schema::hasTable('iefs')
            ? DB::table('iefs')->when($this->iaScopeId !== null, fn ($q) => $q->where('ia_id', $this->iaScopeId))->orderBy('libelle')->get(['id', 'ia_id', 'libelle'])
            : collect();
        $trainingCenterScopes = Schema::hasTable('lieu_de_services')
            ? DB::table('lieu_de_services')->whereIn('id', $trainingCenters->keys())->get(['id', 'ia_id', 'ief_id'])->keyBy('id')
            : collect();

        return [
            'statuts' => $statuts->map(fn (string $label, int|string $id): array => [
                'id' => (int) $id,
                'label' => $label,
            ])->values(),
            'ias' => $ias->map(fn (string $label, int|string $id): array => [
                'id' => (int) $id,
                'label' => $label,
            ])->values(),
            'iefs' => $iefIa->map(fn (object $ief): array => [
                'id' => (int) $ief->id,
                'ia_id' => (int) $ief->ia_id,
                'label' => $ief->libelle,
            ])->values(),
            'matricules' => $items->map(fn (PayrollPayslip $payslip): array => [
                'value' => $payslip->enseignant->matricule,
                'label' => ($payslip->enseignant->matricule ?: 'Sans matricule')
                    .' — '.$this->teacherName($payslip->enseignant),
            ])->unique('value')->sortBy('value')->values(),
            'payment_places' => $paymentPlaces->map(fn (string $label, int|string $id): array => [
                'id' => (int) $id,
                'label' => $label,
            ])->values(),
            'training_centers' => $trainingCenters->map(fn (string $label, int|string $id): array => [
                'id' => (int) $id,
                'ia_id' => $trainingCenterScopes->get($id)?->ia_id,
                'ief_id' => $trainingCenterScopes->get($id)?->ief_id,
                'label' => $label,
            ])->values(),
        ];
    }

    private function salaryLineAmount(PayrollPayslip $payslip, array $codes): float
    {
        return (float) $payslip->lines->whereIn('code', $codes)->sum('amount');
    }

    private function statementAmount(mixed $amount): string
    {
        return number_format((float) $amount, 0, ',', ' ');
    }

    private function teacherCorpsId(Enseignant $teacher): ?int
    {
        if (!$teacher->isFonctionnaire()) return null;
        $id = $teacher->getAttribute('corps_id')
            ?? $teacher->getAttribute('corps_enseignant_id');

        return $id === null ? null : (int) $id;
    }

    private function teacherFirstName(Enseignant $teacher): string
    {
        return trim((string) ($teacher->user?->prenom ?? $teacher->prenom ?? '')) ?: '—';
    }

    private function teacherLastName(Enseignant $teacher): string
    {
        return trim((string) ($teacher->user?->nom ?? $teacher->nom ?? '')) ?: '—';
    }

    private function teacherAccountNumber(Enseignant $teacher): string
    {
        return (string) (
            $teacher->getAttribute('numero_compte')
            ?: $teacher->getAttribute('numero_compte_bancaire')
            ?: 'Non renseigné'
        );
    }

    private function teacherTaxParts(Enseignant $teacher): string
    {
        return (string) (
            $teacher->getAttribute('nombre_parts')
            ?: $teacher->getAttribute('nombre_parts_fiscales')
            ?: '—'
        );
    }

    private function academicYearForPeriod(?PayrollPeriod $period): ?object
    {
        if (! $period || ! Schema::hasTable('annee_academiques')) {
            return null;
        }

        return DB::table('annee_academiques')
            ->whereDate('date_debut', '<=', $period->start_date->toDateString())
            ->whereDate('date_fin', '>=', $period->end_date->toDateString())
            ->first();
    }

    private function referenceMap(string $table, string $labelColumn): Collection
    {
        if (! Schema::hasTable($table)) {
            return collect();
        }

        return DB::table($table)
            ->when($this->iaScopeId !== null && $table === 'ias', fn ($q) => $q->where('id', $this->iaScopeId))
            ->when($this->iaScopeId !== null && $table === 'iefs', fn ($q) => $q->where('ia_id', $this->iaScopeId))
            ->when($table === 'lieu_de_services', fn ($q) => $q->whereNull('deleted_at')
                ->when($this->iaScopeId !== null, fn ($q) => $q->where('ia_id', $this->iaScopeId)))
            ->orderBy($labelColumn)
            ->get(['id', $labelColumn])
            ->mapWithKeys(fn (object $row): array => [(int) $row->id => (string) $row->{$labelColumn}]);
    }

    /** @return Collection<int, array{account: string, bank: string}> */
    private function salaryBankingDetails(Collection $teacherIds): Collection
    {
        if (! Schema::hasTable('comptes_bancaires_enseignants')) {
            return collect();
        }

        $ids = $teacherIds->filter()->map(fn (mixed $id): int => (int) $id)->unique()->values();
        if ($ids->isEmpty()) {
            return collect();
        }

        $accountsQuery = DB::table('comptes_bancaires_enseignants')->whereIn('enseignant_id', $ids);
        if (Schema::hasColumn('comptes_bancaires_enseignants', 'est_principal')) {
            $accountsQuery->orderByDesc('est_principal');
        }
        $accounts = $accountsQuery->orderBy('id')->get()->groupBy('enseignant_id')->map->first();
        $bankTable = collect(['instituts_financieres', 'institution_financieres'])
            ->first(fn (string $table): bool => Schema::hasTable($table));
        $banks = collect();

        if ($bankTable) {
            $columns = Schema::getColumnListing($bankTable);
            $labelColumn = collect(['nom', 'libelle', 'raison_sociale', 'sigle', 'code'])
                ->first(fn (string $column): bool => in_array($column, $columns, true));
            if ($labelColumn) {
                $banks = DB::table($bankTable)
                    ->get(['id', $labelColumn])
                    ->mapWithKeys(fn (object $bank): array => [
                        (int) $bank->id => (string) $bank->{$labelColumn},
                    ]);
            }
        }

        return $accounts->map(function (object $account) use ($banks): array {
            $bankId = $account->institut_financier_id ?? $account->institution_financiere_id ?? null;

            return [
                'account' => (string) ($account->numero_compte ?: 'Non renseigné'),
                'bank' => $banks->get((int) $bankId, $account->code_banque ?: 'Non renseignée'),
            ];
        });
    }
}
