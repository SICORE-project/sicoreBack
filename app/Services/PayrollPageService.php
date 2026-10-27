<?php

namespace App\Services;

use App\Models\Enseignant;
use App\Models\ias;
use App\Models\iefs;
use App\Models\PayrollAttendance;
use App\Models\PayrollElement;
use App\Models\PayrollPayslip;
use App\Models\PayrollPeriod;
use App\Services\Paie\EtatSalaire\EtatSalaireReport;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class PayrollPageService
{
    use EtatSalaireReport;

    private ?int $iaScopeId = null;

    public function forIa(int $iaId): self
    {
        $scoped = clone $this;
        $scoped->iaScopeId = $iaId;
        return $scoped;
    }

    private function scopedTeachers(): Builder
    {
        return Enseignant::query()->when($this->iaScopeId !== null,
            fn ($query) => $query->where('ia_id', $this->iaScopeId)->whereNull('deleted_at'));
    }

    private function scopePayroll(Builder $query): Builder
    {
        return $query->when($this->iaScopeId !== null,
            fn ($query) => $query->whereIn('enseignant_id', $this->scopedTeachers()->select('id')));
    }

    private function periods(): Collection
    {
        $codes = app(PayrollPeriodCatalogue::class)->synchronize();
        $periods = PayrollPeriod::query()->whereIn('code', $codes)->withCount([
            'payslips' => fn ($q) => $this->scopePayroll($q),
            'attendances' => fn ($q) => $this->scopePayroll($q),
            'elements' => fn ($q) => $this->scopePayroll($q),
        ])->latest('start_date')->get();
        if ($this->iaScopeId !== null) {
            foreach ($periods as $period) {
                $slips = $this->scopePayroll(PayrollPayslip::query())->where('payroll_period_id', $period->id);
                $period->employee_count = $period->payslips_count;
                $period->total_gross = (clone $slips)->sum('gross_amount');
                $period->total_deductions = (clone $slips)->sum('deduction_amount');
                $period->total_net = (clone $slips)->sum('net_amount');
            }
        }
        return $periods;
    }
    private const RESULT_SLUGS = [
        'paie-recap-banque',
        'paie-cotisations-sociales',
        'paie-etat-salaires',
        'paie-generee-ief',
        'paie-edition-salaires-banque',
        'paie-bulletins',
        'paie-sommes-percues',
        'paie-recap-elements-corps',
        'paie-montants-engages-banque',
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
        'paie-edition-enseignants',
        'paie-prime-scolaire',
        'paie-reliquats',
        'paie-double-flux',
        'paie-directeurs-interim',
        'paie-cumul-enseignants-ief',
        'paie-recap-elements-corps',
        'paie-edition-fonctionnaires',
        'paie-mutuelles-sante',
        'paie-situation-affectations',
        'paie-montants-engages-banque',
        'paie-heures-supplementaires-interim',
    ];

    /** @return array<string, mixed> */
    public function page(string $slug, ?int $periodId = null, array $criteria = []): array
    {
        if (! in_array($slug, self::SLUGS, true)) {
            throw ValidationException::withMessages(['slug' => 'Page de paie inconnue.']);
        }

        $periods = $this->periods();
        $period = $periodId
            ? $periods->firstWhere('id', $periodId)
            : $this->defaultPeriod($slug, $periods);

        if ($periodId && ! $period) {
            throw ValidationException::withMessages(['period_id' => 'Période de paie inconnue.']);
        }

        $teacherRelations = ['user'];
        if (Schema::hasTable('iefs')) {
            $teacherRelations[] = 'ief';
        }
        if (Schema::hasTable('etablissements')) {
            $teacherRelations[] = 'etablissement.ief.ia';
        }
        if (Schema::hasTable('corps_enseignant')) {
            $teacherRelations[] = 'corps';
        }
        if (Schema::hasTable('institution_financieres')) {
            $teacherRelations[] = 'institutionFinanciere';
        }
        if (Schema::hasTable('mutuelles') && Schema::hasColumn('enseignants', 'mutuelle_id')) {
            $teacherRelations[] = 'mutuelle';
        }
        $activeColumn = Schema::hasColumn('enseignants', 'actif') ? 'actif' : 'est_actif';
        $teachers = $this->scopedTeachers()
            ->with($teacherRelations)
            ->where($activeColumn, true)
            ->orderBy('matricule')
            ->get();
        $academicInspections = ias::query()
            ->when($this->iaScopeId !== null, fn ($q) => $q->where('id', $this->iaScopeId))
            ->orderBy('libelle')
            ->get();
        $educationInspections = iefs::query()
            ->when($this->iaScopeId !== null, fn ($q) => $q->where('ia_id', $this->iaScopeId))
            ->orderBy('libelle')
            ->get();
        $teachingCorps = DB::table('statuts_enseignant')
            ->whereRaw("(LOWER(libelle) LIKE '%contractuel%' OR LOWER(libelle) LIKE '%vacataire%')")
            ->orderBy('libelle')
            ->get();
        $activeYearColumn = Schema::hasColumn('annee_academiques', 'est_active')
            ? 'est_active'
            : 'en cours';
        $academicYears = DB::table('annee_academiques')
            ->where('est_cloturee', false)
            ->orderByDesc($activeYearColumn)
            ->orderByDesc('date_debut')
            ->get();

        $report = $this->report($slug, $period, $teachers, $criteria);
        if ($periods->isEmpty()) {
            $report['notice'] = 'Renseignez les périodes de paie dans Paramétrage (code 01 à 12, nom du mois ou AAAA-MM), puis une année académique avec ses dates. Les mois seront disponibles ici automatiquement.';
        }
        if (in_array($slug, ['paie-avance-tabaski', 'paie-retenue-tabaski'], true)) {
            $missingReferences = collect([
                $academicYears->isEmpty() ? 'une année académique' : null,
                $teachingCorps->isEmpty() ? 'un corps enseignant' : null,
                $academicInspections->isEmpty() ? 'une IA' : null,
            ])->filter()->values();
            if ($missingReferences->isNotEmpty()) {
                $report['actions'] = [$this->exportAction()];
                $report['notice'] = 'Renseignez dans Paramétrage : '.$missingReferences->implode(', ').'. Les formateurs enregistrés restent visibles ci-dessous.';
            }
        }
        $rowFilters = $this->rowFilters($report['rows'], $teachers);
        $personnelProfiles = app(PayrollTeacherProfile::class);

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
            'teaching_statuses' => $teachingCorps->map(fn (object $corps): array => [
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
                'is_active' => (bool) $year->{$activeYearColumn},
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
                'administrative_profile' => $personnelProfiles->describe($teacher),
                'statut_enseignant_id' => $teacher->statut_enseignant_id,
                'payroll_diploma_level' => $teacher->payroll_diploma_level,
                'payroll_category_level' => $teacher->payroll_category_level,
                'salary_base' => $teacher->salaire_base,
                'payroll_profile_configured' => (bool) $teacher->payroll_profile_configured_at,
            ])->values(),
            'stats' => $report['stats'] ?? $this->commonStats($period, $teachers->count()),
            'columns' => $report['columns'],
            'rows' => $report['rows'],
            'salary_statement' => $report['salary_statement'] ?? null,
            'report_catalog' => $report['report_catalog'] ?? [],
            'row_filters' => $rowFilters,
            'supports_hierarchy_filter' => collect($report['columns'])
                ->contains(fn (mixed $column): bool => $this->normalizeColumn((string) $column) === 'matricule'),
            'filters' => $this->filters($periods, $period, $slug),
            'actions' => $report['actions'] ?? $this->reportActions($slug, $period),
            'input_records' => $report['input_records'] ?? [],
            'notice' => $report['notice'] ?? $this->periodNotice($period),
        ];
    }

    /** @return array<string, mixed> */
    private function report(
        string $slug,
        ?PayrollPeriod $period,
        Collection $teachers,
        array $criteria = []
    ): array
    {
        return match ($slug) {
            'paie-etats-presence' => $this->attendanceReport($period),
            'paie-avance-tabaski' => $this->elementReport($period, 'TABASKI_AVANCE', 'Avance Tabaski', 'earning', $teachers),
            'paie-retenue-tabaski' => $this->elementReport($period, 'TABASKI_RETENUE', 'Retenue Tabaski', 'deduction', $teachers),
            'paie-retenues-rappel' => $this->elementReport($period, 'RAPPEL_RETENUE', 'Retenue sur rappel', 'deduction', $teachers),
            'paie-exemptions' => $this->exemptionReport($period),
            'paie-travaux-periodiques' => $this->periodReport($period),
            'paie-recap-banque' => $this->bankSummaryReport($period),
            'paie-cotisations-sociales' => $this->contributionReport($period),
            'paie-etat-salaires' => $this->salaryReport($period, $criteria),
            'paie-elements-saisie-dashboard' => $this->elementsDashboard($period),
            'paie-generee-ief' => $this->iefSummaryReport($period),
            'paie-fermeture-periode' => $this->closingReport(),
            'paie-edition-salaires-banque' => $this->bankSalaryReport($period),
            'paie-bulletins' => $this->payslipReport($period, $teachers),
            'paie-effectifs-corps' => $this->workforceReport($teachers),
            'paie-non-generee' => $this->notGeneratedReport($period),
            'paie-sommes-percues' => $this->paidReport($period),
            'paie-edition-enseignants' => $this->teacherRosterReport($teachers),
            'paie-prime-scolaire' => $this->payrollRubricReport($period, ['PRIME_SCOLAIRE'], 'Prime scolaire'),
            'paie-reliquats' => $this->payrollRubricReport($period, ['RELIQUAT'], 'Reliquat'),
            'paie-double-flux' => $this->payrollRubricReport(
                $period,
                ['DOUBLE_FLUX', 'ENCADREUR_ELEVE_MAITRE'],
                'Double flux / encadreur élève-maître'
            ),
            'paie-directeurs-interim' => $this->payrollRubricReport(
                $period,
                ['DIRECTEUR_INTERIM'],
                'Directeur par intérim'
            ),
            'paie-cumul-enseignants-ief' => $this->iefWorkforceReport($teachers),
            'paie-recap-elements-corps' => $this->salaryElementsByCorpsReport($period),
            'paie-edition-fonctionnaires' => $this->civilServantReport($teachers),
            'paie-mutuelles-sante' => $this->mutualHealthReport($teachers),
            'paie-situation-affectations' => $this->currentAssignmentReport($teachers),
            'paie-montants-engages-banque' => $this->bankCommitmentReport($period),
            'paie-heures-supplementaires-interim' => $this->payrollRubricReport(
                $period,
                ['HEURES_SUPPLEMENTAIRES', 'PRINCIPAL_INTERIMAIRE'],
                'Heures supplémentaires / principaux intérimaires'
            ),
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
        string $category,
        Collection $teachers
    ): array {
        $isTabaski = in_array($code, ['TABASKI_AVANCE', 'TABASKI_RETENUE'], true);

        if ($isTabaski) {
            // Les formateurs proviennent de l'Administration. Les lignes Tabaski
            // peuvent concerner plusieurs mois : aucun filtre de période caché.
            $items = PayrollElement::query()
                ->with('period')
                ->where('code', $code)
                ->whereIn('enseignant_id', $teachers->pluck('id'))
                ->latest('updated_at')
                ->get();
            $itemsByTeacher = $items->groupBy('enseignant_id');
            $corps = DB::table('corps_enseignant')->get(['id', 'code', 'libelle'])->keyBy('id');
            $ias = ias::query()->pluck('libelle', 'id');
            $iefs = iefs::query()->pluck('libelle', 'id');
            $rows = $teachers->flatMap(function (Enseignant $teacher) use ($itemsByTeacher, $corps, $ias, $iefs): Collection {
                $teacherItems = $itemsByTeacher->get($teacher->id, collect());
                $baseCorpsId = $teacher->getAttribute('corps_id') ?? $teacher->getAttribute('corps_enseignant_id');
                $baseIaId = $this->teacherIaId($teacher);
                $baseIefId = $this->teacherIefId($teacher);

                $rowFor = function (?PayrollElement $item) use ($teacher, $corps, $ias, $iefs, $baseCorpsId, $baseIaId, $baseIefId): array {
                    $corpsId = $item?->application_corps_id ?? $baseCorpsId;
                    $corpsData = $corps->get((int) $corpsId);
                    $corpsLabel = $corpsData
                        ? $corpsData->code.' — '.$corpsData->libelle
                        : 'Non renseigné';
                    $iaId = $item?->application_ia_id ?? $baseIaId;
                    $iefId = $item?->application_ief_id ?? $baseIefId;
                    $hierarchy = ($ias->get((int) $iaId) ?: 'IA non renseignée')
                        .' / '.($iefs->get((int) $iefId) ?: 'IEF non renseignée');

                    return [
                        $this->teacherName($teacher),
                        $teacher->matricule ?: '—',
                        $corpsLabel,
                        $hierarchy,
                        $item?->academic_year ?: '—',
                        $item?->period?->label ?: '—',
                        $item ? $this->money($item->amount) : '—',
                        $item ? ($item->application_scope === 'collective' ? 'Collective' : 'Individuelle') : '—',
                        $this->statusCell($item ? ($item->is_exempt ? 'exempt' : $item->status) : 'not_applied'),
                        $item && ! $item->is_exempt && $item->period?->isMutable()
                            ? $this->actionCell('Exempter', 'exempt-element', [
                                'payroll_element_id' => $item->id,
                                'expected_version' => $item->version,
                            ])
                            : '—',
                    ];
                };

                return $teacherItems->isEmpty()
                    ? collect([$rowFor(null)])
                    : $teacherItems->map($rowFor);
            })->values();
            $actionCode = $code === 'TABASKI_AVANCE'
                ? 'apply-tabaski-advance'
                : 'apply-tabaski-deduction';
            $actions = [$this->action('Appliquer collectivement', $actionCode, 'primary')];
            if ($period?->isMutable()) {
                $actions[] = $this->action('Valider les éléments', 'validate-elements');
            }
            $actions[] = $this->exportAction();

            return [
                'columns' => [
                    'Enseignant',
                    'Matricule',
                    'Statut enseignant',
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
                    $this->tabaskiStatusLabel($item),
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
                    $this->stat('Formateurs actifs', $teachers->count(), $label, 'EN', 'green'),
                    $this->stat('Montant total', $this->money($items->sum('amount')), 'Toutes les périodes', 'FC', 'blue'),
                    $this->stat('Applications collectives', $items->where('application_scope', 'collective')->count(), 'Lignes tracées', 'EX', 'yellow'),
                    $this->stat('À valider', $items->where('status', 'draft')->count(), 'Contrôle requis', 'CT', 'red'),
                ],
                'notice' => 'Formateurs actifs issus d’Administration ; données IA, IEF et corps issues de Paramétrage. Historique Tabaski de toutes les périodes.',
            ];
        }

        $items = $period
            ? $this->elementsForPeriod($period)->where('code', $code)->get()
            : collect();

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
    private function periodReport(?PayrollPeriod $selectedPeriod): array
    {
        $periods = $this->periods();

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
                $this->action('Générer des bulletins', 'generate-payslips'),
                $this->action('Valider la paie', 'validate-payroll'),
            ],
            'stats' => [
                $this->stat('Rapports disponibles', 22, 'Périmètre des travaux périodiques', 'TP', 'green'),
                $this->stat('Périodes', $periods->count(), 'Historique disponible', 'PE', 'blue'),
                $this->stat('Périodes ouvertes', $periods->where('status', PayrollPeriod::STATUS_OPEN)->count(), 'Traitement autorisé', 'CT', 'yellow'),
                $this->stat('Net de la sélection', $this->money($selectedPeriod?->total_net ?? 0), $selectedPeriod?->label ?? 'Aucune période', 'SP', 'red'),
            ],
            'report_catalog' => $this->periodicReportCatalog(),
            'notice' => 'Choisissez la période puis ouvrez un rapport. Les éditions utilisent uniquement les données du module paie.',
        ];
    }

    /** @return array<int, array<string, string>> */
    private function periodicReportCatalog(): array
    {
        return [
            ['slug' => 'paie-recap-banque', 'label' => 'État récapitulatif par banque', 'description' => 'Masse brute, retenues et net à virer par institution financière.', 'icon' => 'fa-solid fa-building-columns', 'group' => 'Paiements et banques'],
            ['slug' => 'paie-montants-engages-banque', 'label' => 'Montants engagés par banque', 'description' => 'Engagements, paiements et reste à traiter par banque.', 'icon' => 'fa-solid fa-money-bill-transfer', 'group' => 'Paiements et banques'],
            ['slug' => 'paie-edition-salaires-banque', 'label' => 'Édition des salaires par banque', 'description' => 'Détail des virements et comptes masqués des bénéficiaires.', 'icon' => 'fa-solid fa-file-export', 'group' => 'Paiements et banques'],
            ['slug' => 'paie-cotisations-sociales', 'label' => 'Cotisations sociales', 'description' => 'Parts salariale et employeur à reverser pour la période.', 'icon' => 'fa-solid fa-people-group', 'group' => 'États financiers'],
            ['slug' => 'paie-etat-salaires', 'label' => 'État des salaires', 'description' => 'État mensuel officiel avec toutes les rubriques de gains, cotisations et retenues.', 'icon' => 'fa-solid fa-file-invoice-dollar', 'group' => 'États financiers'],
            ['slug' => 'paie-sommes-percues', 'label' => 'Sommes perçues', 'description' => 'Paiements effectivement enregistrés et références de virement.', 'icon' => 'fa-solid fa-wallet', 'group' => 'États financiers'],
            ['slug' => 'paie-bulletins', 'label' => 'Bulletins de salaire', 'description' => 'Consultation détaillée et traçable des bulletins individuels.', 'icon' => 'fa-solid fa-file-lines', 'group' => 'États financiers'],
            ['slug' => 'paie-elements-saisie-dashboard', 'label' => 'Tableau de bord des éléments de salaire', 'description' => 'Synthèse des gains, retenues, validations et exemptions.', 'icon' => 'fa-solid fa-chart-line', 'group' => 'Contrôles et synthèses'],
            ['slug' => 'paie-recap-elements-corps', 'label' => 'Récapitulatif des éléments par corps', 'description' => 'Gains, retenues, charges et net regroupés par corps enseignant.', 'icon' => 'fa-solid fa-layer-group', 'group' => 'Contrôles et synthèses'],
            ['slug' => 'paie-cumul-enseignants-ief', 'label' => 'Cumul des enseignants par IEF', 'description' => 'Effectif actif par IA, IEF et type d’engagement.', 'icon' => 'fa-solid fa-sitemap', 'group' => 'Contrôles et synthèses'],
            ['slug' => 'paie-effectifs-corps', 'label' => 'Effectifs par corps', 'description' => 'Effectif, masse salariale de base et couverture bancaire.', 'icon' => 'fa-solid fa-users', 'group' => 'Contrôles et synthèses'],
            ['slug' => 'paie-generee-ief', 'label' => 'Paie générée par IEF', 'description' => 'IEF disposant de bulletins calculés pour la période.', 'icon' => 'fa-solid fa-circle-check', 'group' => 'Contrôles et synthèses'],
            ['slug' => 'paie-non-generee', 'label' => 'Paie non générée', 'description' => 'Agents à corriger avant une nouvelle génération.', 'icon' => 'fa-solid fa-triangle-exclamation', 'group' => 'Contrôles et synthèses'],
            ['slug' => 'paie-edition-enseignants', 'label' => 'Édition des enseignants', 'description' => 'Liste administrative utile aux contrôles de paie.', 'icon' => 'fa-solid fa-chalkboard-user', 'group' => 'Personnel rattaché à la paie'],
            ['slug' => 'paie-edition-fonctionnaires', 'label' => 'Édition des fonctionnaires', 'description' => 'Agents identifiés comme fonctionnaires dans la source de personnel.', 'icon' => 'fa-solid fa-user-tie', 'group' => 'Personnel rattaché à la paie'],
            ['slug' => 'paie-mutuelles-sante', 'label' => 'Édition mutuelle de santé', 'description' => 'Mutuelle et montant IPM configurés pour chaque agent.', 'icon' => 'fa-solid fa-heart-pulse', 'group' => 'Personnel rattaché à la paie'],
            ['slug' => 'paie-situation-affectations', 'label' => 'Situation des affectations', 'description' => 'Photographie de l’affectation actuelle sans modifier le module personnel.', 'icon' => 'fa-solid fa-location-dot', 'group' => 'Personnel rattaché à la paie'],
            ['slug' => 'paie-prime-scolaire', 'label' => 'Prime scolaire', 'description' => 'Éléments de prime scolaire saisis ou repris dans les bulletins.', 'icon' => 'fa-solid fa-graduation-cap', 'group' => 'Rubriques périodiques'],
            ['slug' => 'paie-reliquats', 'label' => 'Reliquats', 'description' => 'Reliquats de gains ou de régularisations rattachés à la période.', 'icon' => 'fa-solid fa-clock-rotate-left', 'group' => 'Rubriques périodiques'],
            ['slug' => 'paie-double-flux', 'label' => 'Double flux / encadreur élève-maître', 'description' => 'Indemnités périodiques identifiées par leurs rubriques de paie.', 'icon' => 'fa-solid fa-people-arrows-left-right', 'group' => 'Rubriques périodiques'],
            ['slug' => 'paie-directeurs-interim', 'label' => 'Directeurs par intérim', 'description' => 'Montants accordés pour les fonctions exercées par intérim.', 'icon' => 'fa-solid fa-user-shield', 'group' => 'Rubriques périodiques'],
            ['slug' => 'paie-heures-supplementaires-interim', 'label' => 'Heures supplémentaires / principaux intérimaires', 'description' => 'Éléments variables correspondants, avec statut et montant.', 'icon' => 'fa-solid fa-business-time', 'group' => 'Rubriques périodiques'],
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
    private function payslipReport(?PayrollPeriod $period, Collection $teachers): array
    {
        $items = $this->payslips($period);
        $rows = $items->map(fn (PayrollPayslip $item): array => [
            $item->reference,
            $period?->label ?? '—',
            $item->enseignant->matricule ?: '—',
            $this->teacherName($item->enseignant),
            $this->money($item->gross_amount),
            $this->money($item->deduction_amount),
            $this->money($item->net_amount),
            (string) $item->version,
            ['actions' => [[
                'label' => 'Consulter', 'code' => 'view-payslip',
                'payload' => ['payroll_payslip_id' => $item->id],
            ]]],
        ]);
        $existingIds = $items->pluck('enseignant_id');
        $withoutPayslip = $teachers->reject(fn (Enseignant $teacher) => $existingIds->contains($teacher->id));
        $rows = $rows->concat($withoutPayslip->map(fn (Enseignant $teacher): array => [
            'Non calculé', $period?->label ?? '—', $teacher->matricule ?: '—',
            $this->teacherName($teacher), '—', '—', '—', '—', ['actions' => [[
                'label' => 'Générer', 'code' => 'generate-payslips',
                'payload' => ['generation_scope' => 'teacher', 'enseignant_id' => $teacher->id],
            ]]],
        ]))->sortBy(fn (array $row) => (string) $row[2])->values();

        return [
            'columns' => ['Référence', 'Mois du bulletin', 'Matricule', 'Enseignant', 'Brut', 'Retenues', 'Net', 'Version', 'Actions'],
            'rows' => $rows,
            'actions' => [$this->action('Générer des bulletins', 'generate-payslips', 'primary'), $this->exportAction()],
            'notice' => $period
                ? sprintf(
                    '%s — %d bulletin(s) disponible(s), %d enseignant(s) enregistré(s) sans bulletin calculé. Les enseignants apparaissent dès leur enregistrement dans Personnel.',
                    $period->label,
                    $items->count(),
                    $withoutPayslip->count()
                )
                : 'Les enseignants enregistrés dans Personnel sont affichés directement. Renseignez un mois dans Paramétrage pour consulter ses bulletins. Les montants non calculés restent vides.',
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
    $items = collect();

    if ($period) {
        $query = $this->scopedTeachers()
            ->with(['user', 'corps', 'institutionFinanciere'])
            ->where(Schema::hasColumn('enseignants', 'actif') ? 'actif' : 'est_actif', true)
            ->whereDoesntHave('payslips', fn (Builder $query) => $query->where('payroll_period_id', $period->id))
            ->limit(200);

        if (Schema::hasColumn('enseignants', 'deleted_at')) {
            $query->whereNull('deleted_at');
        }

        $items = $query->get();
    }

    return [
        'columns' => ['Matricule', 'Enseignant', 'État du profil', 'Engagement', 'Corps', 'Salaire de base', 'Banque', 'Situation du bulletin', 'Actions'],
        'rows' => $items->map(fn (Enseignant $teacher): array => [
            $teacher->matricule ?: '—',
            $this->teacherName($teacher),
            $teacher->payroll_profile_configured_at
                ? ['value' => 'Profil configuré', 'badge' => 'active']
                : ['value' => 'À configurer', 'badge' => 'pending'],
            match ($teacher->type_engagement) {
                'contractuel' => 'Professeur contractuel',
                'vacataire' => 'Vacataire',
                default => 'Non renseigné',
            },
            $teacher->corps?->libelle ?? 'Non renseigné',
            $this->money($teacher->salaire_base),
            $teacher->institutionFinanciere?->nom ?? 'Non renseignée',
            $this->payrollProfileReason($teacher),
            $this->actionCell(
                'Générer',
                'generate-payslips', [
                    'generation_scope' => 'teacher',
                    'enseignant_id' => $teacher->id,
                ]),
        ])->values(),
        'actions' => [
            $this->action('Générer des bulletins', 'generate-payslips', 'primary'),
            $this->exportAction(),
        ],
        'stats' => [
            $this->stat('Non générés', $items->count(), 'Enseignants actifs', 'NG', 'red'),
            $this->stat('Profils à configurer', $items->whereNull('payroll_profile_configured_at')->count(), 'Donnée bloquante', 'FC', 'yellow'),
            $this->stat('Sans banque', $items->whereNull('institution_financiere_id')->count(), 'Coordonnées manquantes', 'BQ', 'blue'),
            $this->stat('À traiter', $items->count(), 'Profil ou calcul de paie', 'CT', 'green'),
        ],
        'notice' => $period
            ? sprintf(
                'Paie non générée pour %s : un profil configuré reste visible jusqu’à la génération de son bulletin.',
                $period->label
            )
            : 'Sélectionnez un mois de paie pour identifier les bulletins non générés.',
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

    /** @return array<string, mixed> */
    private function teacherRosterReport(Collection $teachers): array
    {
        return [
            'columns' => ['Matricule', 'Enseignant', 'Engagement', 'Corps', 'IA', 'IEF', 'Établissement', 'Banque', 'Profil paie'],
            'rows' => $teachers->map(fn (Enseignant $teacher): array => [
                $teacher->matricule ?: '—',
                $this->teacherName($teacher),
                $this->engagementLabel($teacher->type_engagement),
                $teacher->corps?->libelle ?? 'Non renseigné',
                $teacher->etablissement?->ief?->ia?->libelle ?? 'Non renseignée',
                $teacher->etablissement?->ief?->libelle ?? 'Non renseignée',
                $teacher->etablissement?->libelle ?? 'Non renseigné',
                $teacher->institutionFinanciere?->nom ?? 'Non renseignée',
                $this->statusCell($teacher->payroll_profile_configured_at ? 'validated' : 'pending'),
            ])->values(),
            'actions' => [$this->exportAction()],
            'stats' => [
                $this->stat('Enseignants actifs', $teachers->count(), 'Périmètre paie', 'EN', 'green'),
                $this->stat('Contractuels', $teachers->where('type_engagement', 'contractuel')->count(), 'Professeurs contractuels', 'EC', 'blue'),
                $this->stat('Vacataires', $teachers->where('type_engagement', 'vacataire')->count(), 'Agents vacataires', 'EN', 'yellow'),
                $this->stat('Profils incomplets', $teachers->whereNull('payroll_profile_configured_at')->count(), 'À corriger avant calcul', 'NG', 'red'),
            ],
        ];
    }

    /** @return array<string, mixed> */
    private function payrollRubricReport(?PayrollPeriod $period, array $codes, string $title): array
    {
        $rows = collect();
        $amounts = collect();
        $payslips = $this->payslips($period);

        if ($payslips->isNotEmpty()) {
            foreach ($payslips as $payslip) {
                foreach ($payslip->lines->whereIn('code', $codes) as $line) {
                    $amounts->push((float) $line->amount);
                    $rows->push([
                        $payslip->enseignant->matricule ?: '—',
                        $this->teacherName($payslip->enseignant),
                        $line->code,
                        $line->label,
                        $this->money($line->amount),
                        'Bulletin '.$payslip->reference,
                        $this->statusCell($payslip->payment_status),
                    ]);
                }
            }
        } elseif ($period) {
            $elements = $this->elementsForPeriod($period)->whereIn('code', $codes)->get();
            foreach ($elements as $element) {
                $amounts->push((float) $element->amount);
                $rows->push([
                    $element->enseignant->matricule ?: '—',
                    $this->teacherName($element->enseignant),
                    $element->code,
                    $element->label,
                    $this->money($element->amount),
                    'Élément de paie',
                    $this->statusCell($element->status),
                ]);
            }
        }

        return [
            'columns' => ['Matricule', 'Enseignant', 'Rubrique', 'Libellé', 'Montant', 'Origine', 'Statut'],
            'rows' => $rows->values(),
            'actions' => [$this->exportAction()],
            'stats' => [
                $this->stat('Lignes', $rows->count(), $title, 'CT', 'green'),
                $this->stat('Bénéficiaires', $rows->pluck(0)->unique()->count(), 'Matricules distincts', 'EN', 'blue'),
                $this->stat('Montant total', $this->money($amounts->sum()), 'Période sélectionnée', 'FC', 'yellow'),
                $this->stat('Période', $period?->label ?? '—', $this->statusLabel($period?->status), 'PE', 'red'),
            ],
            'notice' => $rows->isEmpty()
                ? 'Aucune rubrique « '.$title.' » n’est enregistrée pour la période sélectionnée.'
                : 'Les montants proviennent des bulletins calculés ou, avant calcul, des éléments de paie saisis.',
        ];
    }

    /** @return array<string, mixed> */
    private function iefWorkforceReport(Collection $teachers): array
    {
        $groups = $teachers->groupBy(fn (Enseignant $teacher): string => implode('|', [
            $teacher->etablissement?->ief?->ia?->libelle ?? 'IA non renseignée',
            $teacher->etablissement?->ief?->libelle ?? 'IEF non renseignée',
        ]));

        return [
            'columns' => ['IA', 'IEF', 'Contractuels', 'Vacataires', 'Autres', 'Effectif total', 'Masse salariale de base'],
            'rows' => $groups->map(function (Collection $items, string $key): array {
                [$ia, $ief] = array_pad(explode('|', $key, 2), 2, 'Non renseignée');

                return [
                    $ia,
                    $ief,
                    (string) $items->where('type_engagement', 'contractuel')->count(),
                    (string) $items->where('type_engagement', 'vacataire')->count(),
                    (string) $items->whereNotIn('type_engagement', ['contractuel', 'vacataire'])->count(),
                    (string) $items->count(),
                    $this->money($items->sum('salaire_base')),
                ];
            })->values(),
            'actions' => [$this->exportAction()],
            'stats' => [
                $this->stat('IEF couvertes', $groups->count(), 'Affectations actuelles', 'PI', 'green'),
                $this->stat('Enseignants', $teachers->count(), 'Effectif actif', 'EN', 'blue'),
                $this->stat('Contractuels', $teachers->where('type_engagement', 'contractuel')->count(), 'Statut contractuel', 'EC', 'yellow'),
                $this->stat('Vacataires', $teachers->where('type_engagement', 'vacataire')->count(), 'Statut vacataire', 'EN', 'red'),
            ],
        ];
    }

    /** @return array<string, mixed> */
    private function salaryElementsByCorpsReport(?PayrollPeriod $period): array
    {
        $groups = $this->payslips($period)->groupBy(
            fn (PayrollPayslip $payslip): string => $payslip->enseignant->corps?->libelle ?? 'Non renseigné'
        );

        return [
            'columns' => ['Corps', 'Bulletins', 'Gains', 'Retenues salariales', 'Charges employeur', 'Net à payer'],
            'rows' => $groups->map(function (Collection $items, string $corps): array {
                return [
                    $corps,
                    (string) $items->count(),
                    $this->money($items->sum('gross_amount')),
                    $this->money($items->sum('deduction_amount')),
                    $this->money($items->sum('employer_contribution_amount')),
                    $this->money($items->sum('net_amount')),
                ];
            })->values(),
            'actions' => [$this->exportAction()],
            'stats' => [
                $this->stat('Corps', $groups->count(), 'Regroupements disponibles', 'EC', 'green'),
                $this->stat('Bulletins', $groups->flatten(1)->count(), 'Période sélectionnée', 'BS', 'blue'),
                $this->stat('Masse brute', $this->money($groups->flatten(1)->sum('gross_amount')), 'Tous corps', 'BR', 'yellow'),
                $this->stat('Net total', $this->money($groups->flatten(1)->sum('net_amount')), $period?->label ?? '—', 'SP', 'red'),
            ],
        ];
    }

    /** @return array<string, mixed> */
    private function civilServantReport(Collection $teachers): array
    {
        $items = $teachers->filter(fn (Enseignant $teacher): bool => filled($teacher->type_engagement)
            && ! in_array($teacher->type_engagement, ['contractuel', 'vacataire'], true)
        );

        return [
            'columns' => ['Matricule', 'Agent', 'Type', 'Corps', 'IA', 'IEF', 'Établissement', 'Banque'],
            'rows' => $items->map(fn (Enseignant $teacher): array => [
                $teacher->matricule ?: '—',
                $this->teacherName($teacher),
                $this->engagementLabel($teacher->type_engagement),
                $teacher->corps?->libelle ?? 'Non renseigné',
                $teacher->etablissement?->ief?->ia?->libelle ?? 'Non renseignée',
                $teacher->etablissement?->ief?->libelle ?? 'Non renseignée',
                $teacher->etablissement?->libelle ?? 'Non renseigné',
                $teacher->institutionFinanciere?->nom ?? 'Non renseignée',
            ])->values(),
            'actions' => [$this->exportAction()],
            'notice' => $items->isEmpty()
                ? 'Aucun agent de type fonctionnaire n’est actuellement fourni au module paie. Aucun enregistrement du module personnel n’a été créé artificiellement.'
                : 'Cette édition reste en lecture seule et reflète les agents transmis par le module personnel.',
        ];
    }

    /** @return array<string, mixed> */
    private function mutualHealthReport(Collection $teachers): array
    {
        $contractCount = $teachers->where('type_engagement', PayrollReferenceService::CONTRACTUEL)->count();
        $ipm = $contractCount > 0 ? app(PayrollRubricAmounts::class)->resolve(PayrollReferenceService::CONTRACTUEL)['ipm'] : 0;
        return [
            'columns' => ['Matricule', 'Enseignant', 'Mutuelle', 'Montant IPM', 'IA', 'IEF', 'Statut'],
            'rows' => $teachers->map(fn (Enseignant $teacher): array => [
                $teacher->matricule ?: '—',
                $this->teacherName($teacher),
                $teacher->mutuelle?->nom ?? 'Non renseignée',
                $this->money($teacher->type_engagement === PayrollReferenceService::CONTRACTUEL ? $ipm : 0),
                $teacher->etablissement?->ief?->ia?->libelle ?? 'Non renseignée',
                $teacher->etablissement?->ief?->libelle ?? 'Non renseignée',
                $this->statusCell($teacher->mutuelle_id ? 'validated' : 'pending'),
            ])->values(),
            'actions' => [$this->exportAction()],
            'stats' => [
                $this->stat('Agents', $teachers->count(), 'Effectif actif', 'EN', 'green'),
                $this->stat('Avec mutuelle', $teachers->whereNotNull('mutuelle_id')->count(), 'Affiliations renseignées', 'OK', 'blue'),
                $this->stat('Sans mutuelle', $teachers->whereNull('mutuelle_id')->count(), 'Information manquante', 'NG', 'yellow'),
                $this->stat('Montant IPM', $this->money($contractCount * $ipm), 'Montant mensuel issu des rubriques de paie', 'CS', 'red'),
            ],
        ];
    }

    /** @return array<string, mixed> */
    private function currentAssignmentReport(Collection $teachers): array
    {
        return [
            'columns' => ['Matricule', 'Enseignant', 'IA actuelle', 'IEF actuelle', 'Établissement actuel', 'Dernière mise à jour', 'Statut'],
            'rows' => $teachers->map(fn (Enseignant $teacher): array => [
                $teacher->matricule ?: '—',
                $this->teacherName($teacher),
                $teacher->etablissement?->ief?->ia?->libelle ?? 'Non renseignée',
                $teacher->etablissement?->ief?->libelle ?? 'Non renseignée',
                $teacher->etablissement?->libelle ?? 'Non renseigné',
                $teacher->updated_at?->format('d/m/Y H:i') ?? '—',
                $this->statusCell($teacher->etablissement_id ? 'validated' : 'pending'),
            ])->values(),
            'actions' => [$this->exportAction()],
            'notice' => 'Ce rapport présente l’affectation actuelle utile à la paie. L’historique des réaffectations reste la responsabilité du module personnel.',
        ];
    }

    /** @return array<string, mixed> */
    private function bankCommitmentReport(?PayrollPeriod $period): array
    {
        $groups = $this->payslips($period)->groupBy(
            fn (PayrollPayslip $payslip): string => $payslip->enseignant->institutionFinanciere?->nom ?? 'Non renseignée'
        );

        return [
            'columns' => ['Banque', 'Bénéficiaires', 'Montant engagé', 'Montant payé', 'Reste à payer', 'Paiements rejetés'],
            'rows' => $groups->map(function (Collection $items, string $bank): array {
                $engaged = (float) $items->sum('net_amount');
                $paid = (float) $items->where('payment_status', 'paid')->sum('net_amount');

                return [
                    $bank,
                    (string) $items->count(),
                    $this->money($engaged),
                    $this->money($paid),
                    $this->money(max(0, $engaged - $paid)),
                    (string) $items->where('payment_status', 'rejected')->count(),
                ];
            })->values(),
            'actions' => [$this->exportAction()],
            'stats' => [
                $this->stat('Banques', $groups->count(), 'Institutions concernées', 'BQ', 'green'),
                $this->stat('Engagé', $this->money($groups->flatten(1)->sum('net_amount')), 'Net des bulletins', 'BR', 'blue'),
                $this->stat('Payé', $this->money($groups->flatten(1)->where('payment_status', 'paid')->sum('net_amount')), 'Virements enregistrés', 'OK', 'yellow'),
                $this->stat('En attente', $groups->flatten(1)->where('payment_status', 'pending')->count(), $period?->label ?? '—', 'AT', 'red'),
            ],
        ];
    }

    private function elementsForPeriod(PayrollPeriod $period): Builder
    {
        return $this->scopePayroll(PayrollElement::query())
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

        $relations = ['lines', 'enseignant.user'];
        if (Schema::hasTable('corps_enseignant')
            && Schema::hasColumn('enseignants', 'corps_id')) {
            $relations[] = 'enseignant.corps';
        }
        if (Schema::hasTable('institution_financieres')
            && Schema::hasColumn('enseignants', 'institution_financiere_id')) {
            $relations[] = 'enseignant.institutionFinanciere';
        }
        if (Schema::hasTable('mutuelles') && Schema::hasColumn('enseignants', 'mutuelle_id')) {
            $relations[] = 'enseignant.mutuelle';
        }
        if (Schema::hasTable('etablissements') && Schema::hasColumn('enseignants', 'etablissement_id')) {
            $relations[] = 'enseignant.etablissement.ief.ia';
        }

        return $this->scopePayroll(PayrollPayslip::query())
            ->with($relations)
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
    private function filters(Collection $periods, ?PayrollPeriod $selected, string $slug): array
    {
        if (in_array($slug, ['paie-avance-tabaski', 'paie-retenue-tabaski'], true)) {
            return [];
        }

        $isPayslipPage = $slug === 'paie-bulletins';

        return [[
            'name' => 'period_id',
            'label' => $isPayslipPage ? 'Mois du bulletin' : 'Période de paie',
            'value' => $selected?->id,
            'options' => $periods->map(fn (PayrollPeriod $period): array => [
                'value' => $period->id,
                'label' => ($isPayslipPage ? 'Bulletin de ' : '')
                    .$period->label.' — '.$this->statusLabel($period->status),
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
            'month_label' => $period->label,
            'month_number' => (int) $period->start_date->format('n'),
            'year' => (int) $period->start_date->format('Y'),
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
        $directName = trim((string) $teacher->prenom.' '.(string) $teacher->nom);
        if ($directName !== '') {
            return $directName;
        }

        $user = $teacher->user;

        return $user ? trim($user->prenom.' '.$user->nom) : 'Enseignant #'.$teacher->id;
    }

    private function engagementLabel(?string $engagement): string
    {
        return match ($engagement) {
            'contractuel' => 'Professeur contractuel',
            'vacataire' => 'Vacataire',
            'fonctionnaire' => 'Fonctionnaire',
            null, '' => 'Non renseigné',
            default => ucfirst(str_replace('_', ' ', $engagement)),
        };
    }

    /** @return array<int, array{value: int, label: string}> */
    private function payrollMonths(): array
    {
        return app(PayrollPeriodCatalogue::class)->months();
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
        if ($id === null && $teacher->relationLoaded('ief')) {
            $id = $teacher->ief?->ia_id;
        }
        if ($id === null && ! $teacher->relationLoaded('ief') && $this->teacherIefId($teacher) !== null) {
            $id = DB::table('iefs')->where('id', $this->teacherIefId($teacher))->value('ia_id');
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

    private function tabaskiStatusLabel(PayrollElement $element): string
    {
        $corpsId = $element->application_statut_enseignant_id ?? $element->enseignant->statut_enseignant_id;
        $corps = $corpsId
            ? DB::table('statuts_enseignant')->where('id', (int) $corpsId)->first(['code', 'libelle'])
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
            'not_applied' => 'Non appliquée',
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
