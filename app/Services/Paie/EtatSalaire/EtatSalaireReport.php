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
        $ias = $this->referenceMap('ias', 'libelle');
        $iefs = $this->referenceMap('iefs', 'libelle');
        $paymentPlaces = $this->referenceMap('lieux_paiement', 'libelle');
        $trainingCenters = $this->referenceMap('centres_formation', 'nom');
        $banking = $this->salaryBankingDetails($items->pluck('enseignant_id'));
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

            if (filled($criteria['corps_id'] ?? null)
                && $this->teacherCorpsId($teacher) !== (int) $criteria['corps_id']) {
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
                && (int) $teacher->getAttribute('centre_formation_id') !== (int) $criteria['training_center_id']) {
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
        ];
        $recognizedDeductionCodes = [
            'IPRES_SALARIE', 'IPM', 'IMPR', 'TRIMF', 'CHECKOFF_UES', 'CHECKOFF',
            'OEUVRE_SOCIALE', 'TABASKI_RETENUE', 'RAPPEL_RETENUE', 'RETENUE_SUR_RAPPEL',
            'RETENUE_SALAIRE', 'R94', 'ABSENCE',
        ];

        $records = $items->map(function (PayrollPayslip $payslip, int $index) use (
            $banking,
            $corps,
            $ias,
            $iefs,
            $paymentPlaces,
            $trainingCenters,
            $tabaskiAdvances,
            $recognizedDeductionCodes
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
            $corpsId = $this->teacherCorpsId($teacher);
            $iaId = $this->teacherIaId($teacher);
            $iefId = $this->teacherIefId($teacher);

            return [
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
                'category' => $profileCategory ? (string) $profileCategory : '—',
                'corps' => $corps->get($corpsId, '—'),
                'ia' => $ias->get($iaId, 'IA non renseignée'),
                'ief' => $iefs->get($iefId, 'IEF non renseignée'),
                'training_center' => $trainingCenters->get(
                    (int) $teacher->getAttribute('centre_formation_id'),
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
        $columns = $this->salaryStatementColumns($withSignature);
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
                    $corps,
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

    /** @return array<int, array{key: string, label: string, amount?: bool}> */
    private function salaryStatementColumns(bool $withSignature): array
    {
        $columns = [
            ['key' => 'sequence', 'label' => 'N°'],
            ['key' => 'first_name', 'label' => 'Prénoms'],
            ['key' => 'last_name', 'label' => 'Nom'],
            ['key' => 'matricule', 'label' => 'Matricule'],
            ['key' => 'salary_category', 'label' => 'Sal. catég.', 'amount' => true],
            ['key' => 'special_bonus', 'label' => 'Prime spéciale', 'amount' => true],
            ['key' => 'taxable_amount', 'label' => 'Mt imposable', 'amount' => true],
            ['key' => 'compensation_increase', 'label' => 'Indem. comp. + aug.', 'amount' => true],
            ['key' => 'ipres', 'label' => 'IPRES', 'amount' => true],
            ['key' => 'ipm', 'label' => 'IPM', 'amount' => true],
            ['key' => 'impr', 'label' => 'IR / IMPR', 'amount' => true],
            ['key' => 'trimf', 'label' => 'TRIMF', 'amount' => true],
            ['key' => 'union_social', 'label' => 'Check-off / O.S', 'amount' => true],
            ['key' => 'tabaski_advance', 'label' => 'Avance Tabaski', 'amount' => true],
            ['key' => 'reminder', 'label' => 'Rappel', 'amount' => true],
            ['key' => 'salary_deduction', 'label' => 'Retenue', 'amount' => true],
            ['key' => 'reminder_deduction', 'label' => 'Retenue rappel', 'amount' => true],
            ['key' => 'tabaski_deduction', 'label' => 'Ret. Tabaski', 'amount' => true],
            ['key' => 'other_deductions', 'label' => 'Autres ret.', 'amount' => true],
            ['key' => 'gross', 'label' => 'Salaire brut', 'amount' => true],
            ['key' => 'deductions', 'label' => 'Total retenues', 'amount' => true],
            ['key' => 'net', 'label' => 'Net à payer', 'amount' => true],
            ['key' => 'cni', 'label' => 'CNI'],
            ['key' => 'account', 'label' => 'N° compte'],
            ['key' => 'parts', 'label' => 'Parts'],
            ['key' => 'category', 'label' => 'Cat.'],
            ['key' => 'bank', 'label' => 'Banque'],
            ['key' => 'payment_place', 'label' => 'Lieu de paiement'],
            ['key' => 'payment_status_label', 'label' => 'Paiement'],
        ];

        if ($withSignature) {
            $columns[] = ['key' => 'signature', 'label' => 'Émargement'];
        }

        return $columns;
    }

    /** @return array<string, mixed> */
    private function salaryStatementFilterOptions(
        Collection $items,
        Collection $corps,
        Collection $ias,
        Collection $iefs,
        Collection $paymentPlaces,
        Collection $trainingCenters
    ): array {
        $iefIa = Schema::hasTable('iefs')
            ? DB::table('iefs')->when($this->iaScopeId !== null, fn ($q) => $q->where('ia_id', $this->iaScopeId))->orderBy('libelle')->get(['id', 'ia_id', 'libelle'])
            : collect();

        return [
            'corps' => $corps->map(fn (string $label, int|string $id): array => [
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
