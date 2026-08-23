<?php

namespace App\Services;

use App\Models\Enseignant;
use App\Models\ias;
use App\Models\iefs;
use App\Models\PayrollAttendance;
use App\Models\PayrollElement;
use App\Models\PayrollPayslip;
use App\Models\PayrollPeriod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class PayrollPageService
{
    private const RESULT_SLUGS = [
        'paie-recap-banque',
        'paie-cotisations-sociales',
        'paie-etat-salaires',
        'paie-generee-ief',
        'paie-edition-salaires-banque',
        'paie-bulletins',
        'paie-sommes-percues',
    ];

    public const SLUGS = [
        'paie-etats-presence',
        'paie-avance-tabaski',
        'paie-retenue-tabaski',
        'paie-retenues-rappel',
        'paie-exemptions',
        'paie-travaux-periodiques',
        'paie-recap-banque',
        'paie-cotisations-sociales',
        'paie-etat-salaires',
        'paie-elements-saisie-dashboard',
        'paie-generee-ief',
        'paie-fermeture-periode',
        'paie-edition-salaires-banque',
        'paie-bulletins',
        'paie-effectifs-corps',
        'paie-non-generee',
        'paie-sommes-percues',
    ];

    /** @return array<string, mixed> */
    public function page(string $slug, ?int $periodId = null): array
    {
        if (! in_array($slug, self::SLUGS, true)) {
            throw ValidationException::withMessages(['slug' => 'Page de paie inconnue.']);
        }

        $periods = PayrollPeriod::query()->withCount('payslips')->latest('start_date')->get();
        $period = $periodId
            ? $periods->firstWhere('id', $periodId)
            : $this->defaultPeriod($slug, $periods);

        if ($periodId && ! $period) {
            throw ValidationException::withMessages(['period_id' => 'Période de paie inconnue.']);
        }

        $teacherRelations = ['user'];
        if (Schema::hasTable('etablissements')) {
            $teacherRelations[] = 'etablissement.ief.ia';
        }
        $activeColumn = Schema::hasColumn('enseignants', 'actif') ? 'actif' : 'est_actif';
        $teachers = Enseignant::query()
            ->with($teacherRelations)
            ->where($activeColumn, true)
            ->orderBy('matricule')
            ->get();
        $academicInspections = ias::query()
            ->orderBy('libelle')
            ->get();
        $educationInspections = iefs::query()
            ->orderBy('libelle')
            ->get();
        $teachingCorps = DB::table('corps_enseignant')
            ->whereIn('code', ['VAC', 'PC'])
            ->orderByRaw("CASE code WHEN 'VAC' THEN 1 WHEN 'PC' THEN 2 ELSE 3 END")
            ->get();
        $academicYears = DB::table('annee_academiques')
            ->orderByDesc('date_debut')
            ->get();

        $report = $this->report($slug, $period, $teachers);
        $rowFilters = $this->rowFilters($report['rows'], $teachers);

        return [
            'slug' => $slug,
            'generated_at' => now()->toIso8601String(),
            'period' => $period ? $this->periodPayload($period) : null,
            'periods' => $periods->map(fn (PayrollPeriod $item): array => $this->periodPayload($item))->values(),
            'academic_inspections' => $academicInspections->map(fn (ias $inspection): array => [
                'id' => $inspection->id,
                'value' => $inspection->id,
                'label' => $inspection->libelle,
                'code' => $inspection->code,
            ])->values(),
            'education_inspections' => $educationInspections->map(fn (iefs $inspection): array => [
                'id' => $inspection->id,
                'value' => $inspection->id,
                'label' => $inspection->libelle,
                'code' => $inspection->code,
                'ia_id' => $inspection->ia_id,
            ])->values(),
            'teaching_corps' => $teachingCorps->map(fn (object $corps): array => [
                'id' => $corps->id,
                'value' => $corps->id,
                'code' => $corps->code,
                'label' => $corps->code.' — '.$corps->libelle,
            ])->values(),
            'academic_years' => $academicYears->map(fn (object $year): array => [
                'id' => $year->id,
                'value' => $year->id,
                'label' => $year->libelle,
                'start_date' => $year->date_debut,
                'end_date' => $year->date_fin,
            ])->values(),
            'payroll_months' => $this->payrollMonths(),
            'teachers' => $teachers->map(fn (Enseignant $teacher): array => [
                'id' => $teacher->id,
                'value' => $teacher->id,
                'label' => ($teacher->matricule ?: 'Sans matricule').' — '.$this->teacherName($teacher),
                'matricule' => $teacher->matricule,
                'name' => $this->teacherName($teacher),
                'establishment' => $this->teacherEstablishmentLabel($teacher),
                'ief_id' => $this->teacherIefId($teacher),
                'ief_label' => $this->teacherIefLabel($teacher, $educationInspections),
                'ia_id' => $this->teacherIaId($teacher),
                'ia_label' => $this->teacherIaLabel($teacher, $academicInspections),
                'type_engagement' => $teacher->type_engagement,
                'payroll_diploma_level' => $teacher->payroll_diploma_level,
                'payroll_category_level' => $teacher->payroll_category_level,
                'impr_monthly_amount' => $teacher->impr_monthly_amount,
                'trimf_monthly_amount' => $teacher->trimf_monthly_amount,
                'ipm_monthly_amount' => $teacher->ipm_monthly_amount,
                'union_checkoff_monthly_amount' => $teacher->union_checkoff_monthly_amount,
                'salary_base' => $teacher->salaire_base,
                'payroll_profile_configured' => (bool) $teacher->payroll_profile_configured_at,
            ])->values(),
            'stats' => $report['stats'] ?? $this->commonStats($period, $teachers->count()),
            'columns' => $report['columns'],
            'rows' => $report['rows'],
            'row_filters' => $rowFilters,
            'supports_hierarchy_filter' => collect($report['columns'])
                ->contains(fn (mixed $column): bool => $this->normalizeColumn((string) $column) === 'matricule'),
            'filters' => $this->filters($periods, $period),
            'actions' => $report['actions'] ?? $this->reportActions($slug, $period),
            'input_records' => $report['input_records'] ?? [],
            'notice' => $report['notice'] ?? $this->periodNotice($period),
        ];
    }

    /** @return array<string, mixed> */
    private function report(string $slug, ?PayrollPeriod $period, Collection $teachers): array
    {
        return match ($slug) {
            'paie-etats-presence' => $this->attendanceReport($period),
            'paie-avance-tabaski' => $this->elementReport($period, 'TABASKI_AVANCE', 'Avance Tabaski', 'earning'),
            'paie-retenue-tabaski' => $this->elementReport($period, 'TABASKI_RETENUE', 'Retenue Tabaski', 'deduction'),
            'paie-retenues-rappel' => $this->elementReport($period, 'RAPPEL_RETENUE', 'Retenue sur rappel', 'deduction'),
            'paie-exemptions' => $this->exemptionReport($period),
            'paie-travaux-periodiques' => $this->periodReport(),
            'paie-recap-banque' => $this->bankSummaryReport($period),
            'paie-cotisations-sociales' => $this->contributionReport($period),
            'paie-etat-salaires' => $this->salaryReport($period),
            'paie-elements-saisie-dashboard' => $this->elementsDashboard($period),
            'paie-generee-ief' => $this->iefSummaryReport($period),
            'paie-fermeture-periode' => $this->closingReport(),
            'paie-edition-salaires-banque' => $this->bankSalaryReport($period),
            'paie-bulletins' => $this->payslipReport($period),
            'paie-effectifs-corps' => $this->workforceReport($teachers),
            'paie-non-generee' => $this->notGeneratedReport($period),
            'paie-sommes-percues' => $this->paidReport($period),
        };
    }

    /** @return array<string, mixed> */
    private function attendanceReport(?PayrollPeriod $period): array
    {
        $items = $period
            ? PayrollAttendance::query()
                ->with('enseignant.user')
                ->where('payroll_period_id', $period->id)
                ->latest('updated_at')
                ->limit(200)
                ->get()
            : collect();

        return [
            'columns' => ['Enseignant', 'Matricule', 'Absences', 'Retards', 'Retenue estimée', 'Statut', 'Version', 'Actions'],
            'rows' => $items->map(fn (PayrollAttendance $item): array => [
                $this->teacherName($item->enseignant),
                $item->enseignant->matricule ?: '—',
                $item->absence_days.' jour(s)',
                $item->delay_minutes.' min',
                $this->money($item->deduction_amount),
                $this->statusCell($item->status),
                (string) $item->version,
                $this->actionCell('Modifier', 'save-attendance', [
                    'payroll_period_id' => $item->payroll_period_id,
                    'enseignant_id' => $item->enseignant_id,
                    'absence_days' => $item->absence_days,
                    'delay_minutes' => $item->delay_minutes,
                    'deduction_amount' => $item->deduction_amount,
                    'notes' => $item->notes,
                    'expected_version' => $item->version,
                ]),
            ])->values(),
            'actions' => [
                $this->action('Nouvel état', 'save-attendance', 'primary'),
                $this->action('Valider les présences', 'validate-attendance'),
                $this->exportAction(),
            ],
            'input_records' => $items->map(fn (PayrollAttendance $item): array => [
                'action' => 'save-attendance',
                'payroll_period_id' => $item->payroll_period_id,
                'enseignant_id' => $item->enseignant_id,
                'absence_days' => $item->absence_days,
                'delay_minutes' => $item->delay_minutes,
                'deduction_amount' => $item->deduction_amount,
                'notes' => $item->notes,
                'expected_version' => $item->version,
            ])->values(),
            'stats' => [
                $this->stat('Enseignants suivis', $items->count(), 'Période sélectionnée', 'EN', 'green'),
                $this->stat('Absences', $items->sum(fn ($item) => (float) $item->absence_days), 'Jours déclarés', 'AB', 'red'),
                $this->stat('Retards', $items->sum('delay_minutes'), 'Minutes cumulées', 'RT', 'yellow'),
                $this->stat('Retenues estimées', $this->money($items->sum('deduction_amount')), 'Contrôle avant calcul', 'FC', 'blue'),
            ],
        ];
    }

    /** @return array<string, mixed> */
    private function elementReport(
        ?PayrollPeriod $period,
        string $code,
        string $label,
        string $category
    ): array {
        $items = $period
            ? $this->elementsForPeriod($period)->where('code', $code)->get()
            : collect();
        $isTabaski = in_array($code, ['TABASKI_AVANCE', 'TABASKI_RETENUE'], true);

        if ($isTabaski) {
            $actionCode = $code === 'TABASKI_AVANCE'
                ? 'apply-tabaski-advance'
                : 'apply-tabaski-deduction';
            $actions = [];
            if ($period?->isMutable()) {
                $actions[] = $this->action('Appliquer collectivement', $actionCode, 'primary');
                $actions[] = $this->action('Valider les éléments', 'validate-elements');
            }
            $actions[] = $this->exportAction();

            return [
                'columns' => [
                    'Enseignant',
                    'Matricule',
                    'Corps d’enseignement',
                    'IA / IEF',
                    'Année académique',
                    'Mois d’application',
                    'Montant',
                    'Portée',
                    'Statut',
                    'Actions',
                ],
                'rows' => $items->map(fn (PayrollElement $item): array => [
                    $this->teacherName($item->enseignant),
                    $item->enseignant->matricule ?: '—',
                    $this->tabaskiCorpsLabel($item),
                    $this->tabaskiHierarchyLabel($item),
                    $item->academic_year ?: 'Non renseignée',
                    $period?->label ?? '—',
                    $this->money($item->amount),
                    $item->application_scope === 'collective' ? 'Collective' : 'Individuelle',
                    $this->statusCell($item->status),
                    $item->is_exempt
                        ? '—'
                        : $this->actionCell('Exempter', 'exempt-element', [
                            'payroll_element_id' => $item->id,
                            'expected_version' => $item->version,
                        ]),
                ])->values(),
                'actions' => $actions,
                'stats' => [
                    $this->stat('Dossiers', $items->count(), $label, 'EN', 'green'),
                    $this->stat('Montant total', $this->money($items->sum('amount')), 'Groupe sélectionné', 'FC', 'blue'),
                    $this->stat('Applications collectives', $items->where('application_scope', 'collective')->count(), 'Lignes tracées', 'EX', 'yellow'),
                    $this->stat('À valider', $items->where('status', 'draft')->count(), 'Contrôle requis', 'CT', 'red'),
                ],
            ];
        }

        return [
            'columns' => ['Enseignant', 'Matricule', 'Libellé', 'Montant', 'Catégorie', 'Exemption', 'Statut', 'Actions'],
            'rows' => $items->map(fn (PayrollElement $item): array => [
                $this->teacherName($item->enseignant),
                $item->enseignant->matricule ?: '—',
                $item->label,
                $this->money($item->amount),
                $this->categoryLabel($item->category),
                $item->is_exempt ? $this->statusCell('exempt') : 'Non',
                $this->statusCell($item->status),
                $item->is_exempt
                    ? '—'
                    : $this->actionCell('Exempter', 'exempt-element', [
                        'payroll_element_id' => $item->id,
                        'expected_version' => $item->version,
                    ]),
            ])->values(),
            'actions' => [
                $this->action('Ajouter', 'add-element', 'primary', [
                    'code' => $code,
                    'label' => $label,
                    'category' => $category,
                ]),
                $this->action('Valider les éléments', 'validate-elements'),
                $this->exportAction(),
            ],
            'input_records' => $this->elementInputRecords($items),
            'stats' => [
                $this->stat('Dossiers', $items->count(), $label, 'EN', 'green'),
                $this->stat('Montant brut', $this->money($items->sum('amount')), 'Avant exemptions', 'FC', 'blue'),
                $this->stat('Exemptions', $items->where('is_exempt', true)->count(), 'Éléments neutralisés', 'EX', 'yellow'),
                $this->stat('À valider', $items->where('status', 'draft')->count(), 'Contrôle requis', 'CT', 'red'),
            ],
        ];
    }

    /** @return array<string, mixed> */
    private function exemptionReport(?PayrollPeriod $period): array
    {
        $items = $period
            ? $this->elementsForPeriod($period)->where('is_exempt', true)->get()
            : collect();

        return [
            'columns' => ['Enseignant', 'Matricule', 'Élément', 'Catégorie', 'Montant neutralisé', 'Motif', 'Version'],
            'rows' => $items->map(fn (PayrollElement $item): array => [
                $this->teacherName($item->enseignant),
                $item->enseignant->matricule ?: '—',
                $item->label,
                $this->categoryLabel($item->category),
                $this->money($item->amount),
                $item->exemption_reason ?: '—',
                (string) $item->version,
            ])->values(),
            'actions' => [$this->exportAction()],
        ];
    }

    /** @return array<string, mixed> */
    private function periodReport(): array
    {
        $periods = PayrollPeriod::query()->withCount(['attendances', 'elements', 'payslips'])->latest('start_date')->get();

        return [
            'columns' => ['Période', 'Statut', 'Présences', 'Éléments', 'Bulletins', 'Brut', 'Retenues', 'Net', 'Version'],
            'rows' => $periods->map(fn (PayrollPeriod $item): array => [
                $item->label,
                $this->statusCell($item->status),
                (string) $item->attendances_count,
                (string) $item->elements_count,
                (string) $item->payslips_count,
                $this->money($item->total_gross),
                $this->money($item->total_deductions),
                $this->money($item->total_net),
                (string) $item->version,
            ])->values(),
            'actions' => [
                $this->action('Nouvelle période', 'create-period', 'primary'),
                $this->action('Calculer la paie', 'calculate-payroll'),
                $this->action('Valider la paie', 'validate-payroll'),
            ],
        ];
    }

    /** @return array<string, mixed> */
    private function bankSummaryReport(?PayrollPeriod $period): array
    {
        $groups = $this->payslips($period)->groupBy(
            fn (PayrollPayslip $item): string => $item->enseignant->institutionFinanciere?->nom ?? 'Non renseignée'
        );

        return [
            'columns' => ['Institution financière', 'Bénéficiaires', 'Brut', 'Retenues', 'Net à virer', 'Payés', 'En attente'],
            'rows' => $groups->map(function (Collection $items, string $bank): array {
                return [
                    $bank,
                    (string) $items->count(),
                    $this->money($items->sum('gross_amount')),
                    $this->money($items->sum('deduction_amount')),
                    $this->money($items->sum('net_amount')),
                    (string) $items->where('payment_status', 'paid')->count(),
                    (string) $items->where('payment_status', 'pending')->count(),
                ];
            })->values(),
            'actions' => [$this->exportAction()],
        ];
    }

    /** @return array<string, mixed> */
    private function contributionReport(?PayrollPeriod $period): array
    {
        $payslips = $this->payslips($period);
        $employeeCodes = ['COTISATION_SOCIALE', 'IPRES_SALARIE'];
        $employee = $payslips
            ->flatMap->lines
            ->whereIn('code', $employeeCodes)
            ->sum('amount');
        $employer = $payslips->sum('employer_contribution_amount');
        $contributors = $payslips->filter(fn (PayrollPayslip $payslip): bool => (float) $payslip->employer_contribution_amount > 0
            || $payslip->lines->whereIn('code', $employeeCodes)->isNotEmpty()
        )->count();

        return [
            'columns' => ['Période', 'Assujettis', 'Part salariale', 'Part employeur', 'Total à reverser', 'Statut'],
            'rows' => $period ? [[
                $period->label,
                (string) $contributors,
                $this->money($employee),
                $this->money($employer),
                $this->money($employee + $employer),
                $this->statusCell($period->status),
            ]] : [],
            'actions' => [$this->exportAction()],
            'stats' => [
                $this->stat('Assujettis', $contributors, 'Bulletins avec cotisations', 'EN', 'green'),
                $this->stat('Part salariale', $this->money($employee), 'Retenue sur net', 'CS', 'blue'),
                $this->stat('Part employeur', $this->money($employer), 'Charge employeur', 'FC', 'yellow'),
                $this->stat('Total cotisations', $this->money($employee + $employer), 'À reverser', 'SP', 'red'),
            ],
        ];
    }

    /** @return array<string, mixed> */
    private function salaryReport(?PayrollPeriod $period): array
    {
        $items = $this->payslips($period);

        return [
            'columns' => ['Matricule', 'Enseignant', 'Brut', 'Retenues', 'Net à payer', 'Banque', 'Paiement'],
            'rows' => $items->map(fn (PayrollPayslip $item): array => [
                $item->enseignant->matricule ?: '—',
                $this->teacherName($item->enseignant),
                $this->money($item->gross_amount),
                $this->money($item->deduction_amount),
                $this->money($item->net_amount),
                $item->enseignant->institutionFinanciere?->nom ?? 'Non renseignée',
                $this->statusCell($item->payment_status),
            ])->values(),
            'actions' => [$this->exportAction()],
        ];
    }

    /** @return array<string, mixed> */
    private function elementsDashboard(?PayrollPeriod $period): array
    {
        $items = $period ? $this->elementsForPeriod($period)->get() : collect();
        $groups = $items->groupBy('category');

        return [
            'columns' => ['Catégorie', 'Éléments', 'Montant', 'Validés', 'Brouillons', 'Exemptés'],
            'rows' => $groups->map(fn (Collection $group, string $category): array => [
                $this->categoryLabel($category),
                (string) $group->count(),
                $this->money($group->sum('amount')),
                (string) $group->where('status', 'validated')->count(),
                (string) $group->where('status', 'draft')->count(),
                (string) $group->where('is_exempt', true)->count(),
            ])->values(),
            'actions' => [
                $this->action('Ajouter un élément', 'add-element', 'primary'),
                $this->action('Valider les éléments', 'validate-elements'),
                $this->exportAction(),
            ],
            'input_records' => $this->elementInputRecords($items),
        ];
    }

    /**
     * Expose uniquement les valeurs nécessaires pour reprendre une saisie
     * existante avec sa version courante. Le navigateur peut ainsi distinguer
     * une création d'une modification sans contourner le verrou optimiste.
     *
     * @return Collection<int, array<string, mixed>>
     */
    private function elementInputRecords(Collection $items): Collection
    {
        return $items->map(fn (PayrollElement $item): array => [
            'action' => 'add-element',
            'payroll_period_id' => $item->payroll_period_id,
            'enseignant_id' => $item->enseignant_id,
            'code' => $item->code,
            'label' => $item->label,
            'category' => $item->category,
            'amount' => $item->amount,
            'expected_version' => $item->version,
        ])->values();
    }

    /** @return array<string, mixed> */
    private function iefSummaryReport(?PayrollPeriod $period): array
    {
        $groups = $this->payslips($period)->groupBy(function (PayrollPayslip $item): string {
            return $item->enseignant->etablissement?->ief?->libelle ?? 'IEF non renseignée';
        });

        return [
            'columns' => ['IEF', 'Bulletins générés', 'Masse brute', 'Retenues', 'Net', 'Statut'],
            'rows' => $groups->map(fn (Collection $items, string $ief): array => [
                $ief,
                (string) $items->count(),
                $this->money($items->sum('gross_amount')),
                $this->money($items->sum('deduction_amount')),
                $this->money($items->sum('net_amount')),
                $period ? $this->statusCell($period->status) : '—',
            ])->values(),
            'actions' => [$this->exportAction()],
        ];
    }

    /** @return array<string, mixed> */
    private function closingReport(): array
    {
        $periods = PayrollPeriod::query()->latest('start_date')->get();

        return [
            'columns' => ['Période', 'Statut', 'Effectif', 'Net total', 'Calculée le', 'Validée le', 'Clôturée le', 'Version'],
            'rows' => $periods->map(fn (PayrollPeriod $item): array => [
                $item->label.' ('.$item->code.')',
                $this->statusCell($item->status),
                (string) $item->employee_count,
                $this->money($item->total_net),
                $item->calculated_at?->format('d/m/Y H:i') ?? '—',
                $item->validated_at?->format('d/m/Y H:i') ?? '—',
                $item->closed_at?->format('d/m/Y H:i') ?? '—',
                (string) $item->version,
            ])->values(),
            'actions' => [$this->action('Fermer la période', 'close-period', 'danger')],
            'notice' => 'La clôture est définitive. Le code exact de la période et sa version courante sont exigés.',
        ];
    }

    /** @return array<string, mixed> */
    private function bankSalaryReport(?PayrollPeriod $period): array
    {
        $items = $this->payslips($period)->sortBy(
            fn (PayrollPayslip $item): string => ($item->enseignant->institutionFinanciere?->nom ?? '')
                .'|'.($item->enseignant->matricule ?? '')
        );

        return [
            'columns' => ['Banque', 'Compte', 'Matricule', 'Bénéficiaire', 'Montant à virer', 'Référence bulletin', 'Paiement'],
            'rows' => $items->map(fn (PayrollPayslip $item): array => [
                $item->enseignant->institutionFinanciere?->nom ?? 'Non renseignée',
                $this->maskAccount($item->enseignant->numero_compte),
                $item->enseignant->matricule ?: '—',
                $this->teacherName($item->enseignant),
                $this->money($item->net_amount),
                $item->reference,
                $this->statusCell($item->payment_status),
            ])->values(),
            'actions' => [$this->exportAction()],
        ];
    }

    /** @return array<string, mixed> */
    private function payslipReport(?PayrollPeriod $period): array
    {
        $items = $this->payslips($period);

        return [
            'columns' => ['Référence', 'Matricule', 'Enseignant', 'Brut', 'Retenues', 'Net', 'Paiement', 'Version', 'Actions'],
            'rows' => $items->map(fn (PayrollPayslip $item): array => [
                $item->reference,
                $item->enseignant->matricule ?: '—',
                $this->teacherName($item->enseignant),
                $this->money($item->gross_amount),
                $this->money($item->deduction_amount),
                $this->money($item->net_amount),
                $this->statusCell($item->payment_status),
                (string) $item->version,
                [
                    'actions' => array_values(array_filter([
                        [
                            'label' => 'Consulter',
                            'code' => 'view-payslip',
                            'payload' => ['payroll_payslip_id' => $item->id],
                        ],
                        $item->payment_status === 'paid' ? null : [
                            'label' => 'Marquer payé',
                            'code' => 'mark-paid',
                            'payload' => [
                                'payroll_payslip_id' => $item->id,
                                'expected_version' => $item->version,
                            ],
                        ],
                    ])),
                ],
            ])->values(),
            'actions' => [$this->exportAction()],
        ];
    }

    /** @return array<string, mixed> */
    private function workforceReport(Collection $teachers): array
    {
        $teachers->loadMissing('corps');
        $groups = $teachers->groupBy(fn (Enseignant $teacher): string => $teacher->corps?->libelle ?? 'Non renseigné');

        return [
            'columns' => ['Corps', 'Effectif actif', 'Masse salariale de base', 'Salaire moyen', 'Avec banque', 'Sans banque'],
            'rows' => $groups->map(function (Collection $items, string $corps): array {
                $mass = $items->sum(fn (Enseignant $teacher): float => (float) $teacher->salaire_base);

                return [
                    $corps,
                    (string) $items->count(),
                    $this->money($mass),
                    $this->money($items->count() ? $mass / $items->count() : 0),
                    (string) $items->whereNotNull('institution_financiere_id')->count(),
                    (string) $items->whereNull('institution_financiere_id')->count(),
                ];
            })->values(),
            'actions' => [$this->exportAction()],
        ];
    }

    /** @return array<string, mixed> */
    private function notGeneratedReport(?PayrollPeriod $period): array
    {
        $items = $period
            ? Enseignant::query()
                ->with(['user', 'corps', 'institutionFinanciere'])
                ->where('actif', true)
                ->whereDoesntHave('payslips', fn (Builder $query) => $query->where('payroll_period_id', $period->id))
                ->limit(200)
                ->get()
            : collect();

        return [
            'columns' => ['Matricule', 'Enseignant', 'Engagement', 'Corps', 'Salaire de base', 'Banque', 'Motif probable', 'Actions'],
            'rows' => $items->map(fn (Enseignant $teacher): array => [
                $teacher->matricule ?: '—',
                $this->teacherName($teacher),
                match ($teacher->type_engagement) {
                    'contractuel' => 'Professeur contractuel',
                    'vacataire' => 'Vacataire',
                    default => 'Non renseigné',
                },
                $teacher->corps?->libelle ?? 'Non renseigné',
                $this->money($teacher->salaire_base),
                $teacher->institutionFinanciere?->nom ?? 'Non renseignée',
                $this->payrollProfileReason($teacher),
                $this->actionCell('Configurer', 'configure-teacher-payroll', [
                    'enseignant_id' => $teacher->id,
                ]),
            ])->values(),
            'actions' => [
                $this->action('Configurer un formateur', 'configure-teacher-payroll', 'primary'),
                $this->exportAction(),
            ],
            'stats' => [
                $this->stat('Non générés', $items->count(), 'Enseignants actifs', 'NG', 'red'),
                $this->stat('Profils à configurer', $items->whereNull('payroll_profile_configured_at')->count(), 'Donnée bloquante', 'FC', 'yellow'),
                $this->stat('Sans banque', $items->whereNull('institution_financiere_id')->count(), 'Coordonnées manquantes', 'BQ', 'blue'),
                $this->stat('À corriger', $items->count(), 'Avant validation', 'CT', 'green'),
            ],
        ];
    }

    /** @return array<string, mixed> */
    private function paidReport(?PayrollPeriod $period): array
    {
        $items = $this->payslips($period)->where('payment_status', 'paid');

        return [
            'columns' => ['Référence paiement', 'Date', 'Matricule', 'Bénéficiaire', 'Banque', 'Montant perçu', 'Bulletin'],
            'rows' => $items->map(fn (PayrollPayslip $item): array => [
                $item->payment_reference ?: '—',
                $item->paid_at?->format('d/m/Y H:i') ?? '—',
                $item->enseignant->matricule ?: '—',
                $this->teacherName($item->enseignant),
                $item->enseignant->institutionFinanciere?->nom ?? 'Non renseignée',
                $this->money($item->net_amount),
                $item->reference,
            ])->values(),
            'actions' => [$this->exportAction()],
        ];
    }

    private function elementsForPeriod(PayrollPeriod $period): Builder
    {
        return PayrollElement::query()
            ->with([
                'enseignant.user',
            ])
            ->where('payroll_period_id', $period->id)
            ->latest('updated_at')
            ->limit(200);
    }

    private function payslips(?PayrollPeriod $period): Collection
    {
        if (! $period) {
            return collect();
        }

        return PayrollPayslip::query()
            ->with([
                'lines',
                'enseignant.user',
                'enseignant.institutionFinanciere',
                'enseignant.etablissement.ief',
            ])
            ->where('payroll_period_id', $period->id)
            ->orderBy('reference')
            ->limit(500)
            ->get();
    }

    /** @return array<int, array<string, mixed>> */
    private function commonStats(?PayrollPeriod $period, int $teacherCount): array
    {
        return [
            $this->stat('Enseignants actifs', $teacherCount, 'Éligibles au traitement', 'EN', 'green'),
            $this->stat('Bulletins générés', $period?->employee_count ?? 0, 'Période sélectionnée', 'BS', 'blue'),
            $this->stat('Masse brute', $this->money($period?->total_gross ?? 0), 'Avant retenues', 'FC', 'yellow'),
            $this->stat('Net à payer', $this->money($period?->total_net ?? 0), $this->statusLabel($period?->status), 'SP', 'red'),
        ];
    }

    /** @return array<int, array<string, mixed>> */
    private function filters(Collection $periods, ?PayrollPeriod $selected): array
    {
        return [[
            'name' => 'period_id',
            'label' => 'Période de paie',
            'value' => $selected?->id,
            'options' => $periods->map(fn (PayrollPeriod $period): array => [
                'value' => $period->id,
                'label' => $period->label.' — '.$this->statusLabel($period->status),
            ])->values(),
        ]];
    }

    /** @return array<int, array<string, mixed>> */
    private function reportActions(string $slug, ?PayrollPeriod $period): array
    {
        return [$this->exportAction()];
    }

    /** @return array<int, array<string, int|string|null>|null> */
    private function rowFilters(iterable $rows, Collection $teachers): array
    {
        $teachersByMatricule = $teachers
            ->filter(fn (Enseignant $teacher): bool => filled($teacher->matricule))
            ->keyBy(fn (Enseignant $teacher): string => $this->normalizeMatricule($teacher->matricule));

        return collect($rows)->map(function (mixed $row) use ($teachersByMatricule): ?array {
            if (! is_iterable($row)) {
                return null;
            }

            foreach ($row as $cell) {
                if (! is_string($cell) && ! is_numeric($cell)) {
                    continue;
                }

                $teacher = $teachersByMatricule->get($this->normalizeMatricule((string) $cell));
                if ($teacher) {
                    return [
                        'ia_id' => $this->teacherIaId($teacher),
                        'ief_id' => $this->teacherIefId($teacher),
                        'matricule' => $teacher->matricule,
                    ];
                }
            }

            return null;
        })->values()->all();
    }

    private function normalizeMatricule(?string $matricule): string
    {
        return mb_strtoupper(trim((string) $matricule));
    }

    private function normalizeColumn(string $column): string
    {
        return mb_strtolower(trim($column));
    }

    private function defaultPeriod(string $slug, Collection $periods): ?PayrollPeriod
    {
        if (in_array($slug, self::RESULT_SLUGS, true)) {
            $periodWithPayslips = $periods->first(
                fn (PayrollPeriod $period): bool => (int) $period->payslips_count > 0
            );
            if ($periodWithPayslips) {
                return $periodWithPayslips;
            }
        }

        return $periods->firstWhere('status', PayrollPeriod::STATUS_OPEN) ?? $periods->first();
    }

    private function payrollProfileReason(Enseignant $teacher): string
    {
        if (! in_array($teacher->type_engagement, ['contractuel', 'vacataire'], true)) {
            return 'Type d’engagement absent';
        }
        if (! $teacher->payroll_profile_configured_at) {
            return 'Profil de paie à compléter';
        }
        if ($teacher->impr_monthly_amount === null || $teacher->trimf_monthly_amount === null) {
            return 'IMPR ou TRIMF à valider';
        }
        if ($teacher->type_engagement === 'contractuel' && (
            ! $teacher->payroll_diploma_level
            || ! $teacher->payroll_category_level
        )) {
            return 'Diplôme ou catégorie absent';
        }
        if ((float) $teacher->salaire_base <= 0) {
            return 'Grille salariale non appliquée';
        }

        return 'Calcul non exécuté';
    }

    /** @return array<string, mixed> */
    private function action(string $label, string $code, string $style = 'secondary', array $defaults = []): array
    {
        return [
            'label' => $label,
            'code' => $code,
            'style' => $style,
            'defaults' => $defaults,
        ];
    }

    /** @return array<string, mixed> */
    private function exportAction(): array
    {
        return [
            'label' => 'Exporter CSV',
            'code' => 'export',
            'style' => 'secondary',
        ];
    }

    /** @return array<string, mixed> */
    private function actionCell(string $label, string $code, array $payload): array
    {
        return [
            'actions' => [[
                'label' => $label,
                'code' => $code,
                'payload' => $payload,
            ]],
        ];
    }

    /** @return array<string, string> */
    private function statusCell(string $status): array
    {
        $variant = match ($status) {
            'validated', 'paid', 'closed' => 'active',
            'calculated', 'open' => 'primary',
            'draft', 'pending' => 'pending',
            'rejected' => 'inactive',
            'exempt' => 'suspended',
            default => 'pending',
        };

        return [
            'value' => $this->statusLabel($status),
            'badge' => $variant,
        ];
    }

    /** @return array<string, mixed> */
    private function stat(string $label, mixed $value, string $note, string $icon, string $color): array
    {
        return compact('label', 'value', 'note', 'icon', 'color');
    }

    /** @return array<string, mixed> */
    private function periodPayload(PayrollPeriod $period): array
    {
        return [
            'id' => $period->id,
            'code' => $period->code,
            'label' => $period->label,
            'status' => $period->status,
            'status_label' => $this->statusLabel($period->status),
            'version' => $period->version,
            'start_date' => $period->start_date->toDateString(),
            'end_date' => $period->end_date->toDateString(),
        ];
    }

    private function periodNotice(?PayrollPeriod $period): string
    {
        if (! $period) {
            return 'Aucune période n’est configurée. Créez d’abord une période de paie.';
        }

        return sprintf(
            'Période active : %s (%s) — statut %s.',
            $period->label,
            $period->code,
            mb_strtolower($this->statusLabel($period->status))
        );
    }

    private function teacherName(Enseignant $teacher): string
    {
        $user = $teacher->user;
        if (! $user) {
            $directName = trim((string) $teacher->prenom.' '.(string) $teacher->nom);

            return $directName !== '' ? $directName : 'Enseignant #'.$teacher->id;
        }

        return trim($user->prenom.' '.$user->nom);
    }

    /** @return array<int, array{value: int, label: string}> */
    private function payrollMonths(): array
    {
        return collect([
            1 => 'Janvier',
            2 => 'Février',
            3 => 'Mars',
            4 => 'Avril',
            5 => 'Mai',
            6 => 'Juin',
            7 => 'Juillet',
            8 => 'Août',
            9 => 'Septembre',
            10 => 'Octobre',
            11 => 'Novembre',
            12 => 'Décembre',
        ])->map(fn (string $label, int $value): array => compact('value', 'label'))->values()->all();
    }

    private function teacherEstablishmentLabel(Enseignant $teacher): ?string
    {
        return $teacher->relationLoaded('etablissement') ? $teacher->etablissement?->libelle : null;
    }

    private function teacherIefId(Enseignant $teacher): ?int
    {
        $id = $teacher->getAttribute('ief_id');
        if ($id === null && $teacher->relationLoaded('etablissement')) {
            $id = $teacher->etablissement?->ief_id;
        }

        return $id === null ? null : (int) $id;
    }

    private function teacherIaId(Enseignant $teacher): ?int
    {
        $id = $teacher->getAttribute('ia_id');
        if ($id === null && $teacher->relationLoaded('etablissement')) {
            $id = $teacher->etablissement?->ief?->ia_id;
        }

        return $id === null ? null : (int) $id;
    }

    private function teacherIefLabel(Enseignant $teacher, Collection $inspections): ?string
    {
        return $inspections->firstWhere('id', $this->teacherIefId($teacher))?->libelle;
    }

    private function teacherIaLabel(Enseignant $teacher, Collection $inspections): ?string
    {
        return $inspections->firstWhere('id', $this->teacherIaId($teacher))?->libelle;
    }

    private function tabaskiCorpsLabel(PayrollElement $element): string
    {
        $corpsId = $element->application_corps_id ?? $element->enseignant->getAttribute('corps_id');
        $corps = $corpsId
            ? DB::table('corps_enseignant')->where('id', (int) $corpsId)->first(['code', 'libelle'])
            : null;

        return $corps ? $corps->code.' — '.$corps->libelle : 'Non renseigné';
    }

    private function tabaskiHierarchyLabel(PayrollElement $element): string
    {
        $iaId = $element->application_ia_id ?? $this->teacherIaId($element->enseignant);
        $iefId = $element->application_ief_id ?? $this->teacherIefId($element->enseignant);
        $ia = $iaId ? DB::table('ias')->where('id', (int) $iaId)->value('libelle') : null;
        $ief = $iefId ? DB::table('iefs')->where('id', (int) $iefId)->value('libelle') : null;

        return ($ia ?: 'IA non renseignée').' / '.($ief ?: 'IEF non renseignée');
    }

    private function categoryLabel(string $category): string
    {
        return match ($category) {
            'earning' => 'Gain',
            'deduction' => 'Retenue',
            'contribution' => 'Cotisation',
            default => ucfirst($category),
        };
    }

    private function statusLabel(?string $status): string
    {
        return match ($status) {
            'open' => 'Ouverte',
            'calculated' => 'Calculée',
            'validated' => 'Validée',
            'closed' => 'Clôturée',
            'draft' => 'Brouillon',
            'pending' => 'En attente',
            'paid' => 'Payé',
            'rejected' => 'Rejeté',
            'exempt' => 'Exempté',
            default => 'Non initialisée',
        };
    }

    private function money(mixed $amount): string
    {
        return number_format((float) $amount, 0, ',', ' ').' FCFA';
    }

    private function maskAccount(?string $account): string
    {
        if (! $account) {
            return 'Non renseigné';
        }

        return str_repeat('•', max(0, mb_strlen($account) - 4)).mb_substr($account, -4);
    }
}
