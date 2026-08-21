<?php

namespace App\Services;

use App\Models\Enseignant;
use App\Models\PayrollAttendance;
use App\Models\PayrollElement;
use App\Models\PayrollPayslip;
use App\Models\PayrollPeriod;
use App\Models\PayrollRun;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class PayrollActionService
{
    public function __construct(
        private readonly PayrollCalculationService $calculation,
        private readonly PayrollAuditService $audit,
        private readonly PayrollReferenceService $references,
    ) {}

    public function configureTeacherPayroll(
        array $data,
        User $actor,
        Request $request,
        ?string $key
    ): Enseignant {
        return DB::transaction(function () use ($data, $actor, $request, $key): Enseignant {
            $teacher = Enseignant::query()->lockForUpdate()->findOrFail($data['enseignant_id']);
            $this->assertTeacherHierarchy($teacher, $data);

            $engagement = (string) $data['type_engagement'];
            $diploma = $engagement === PayrollReferenceService::CONTRACTUEL
                ? (string) $data['payroll_diploma_level']
                : null;
            $category = $engagement === PayrollReferenceService::CONTRACTUEL
                ? (int) $data['payroll_category_level']
                : null;
            $salary = $this->references->salaryFor($engagement, $diploma, $category, now());
            $before = $teacher->toArray();

            $teacher->update([
                'type_engagement' => $engagement,
                'payroll_diploma_level' => $diploma,
                'payroll_category_level' => $category,
                'diplome' => $diploma ? config('payroll_reference.diplomas.'.$diploma) : null,
                'salaire_base' => $salary,
                'impr_monthly_amount' => round((float) $data['impr_monthly_amount'], 2),
                'trimf_monthly_amount' => round((float) $data['trimf_monthly_amount'], 2),
                'ipm_monthly_amount' => $engagement === PayrollReferenceService::CONTRACTUEL
                    ? round((float) ($data['ipm_monthly_amount'] ?? 0), 2)
                    : 0,
                'union_checkoff_monthly_amount' => $engagement === PayrollReferenceService::CONTRACTUEL
                    ? round((float) ($data['union_checkoff_monthly_amount'] ?? 0), 2)
                    : 0,
                'payroll_profile_configured_at' => now(),
                'payroll_profile_configured_by' => $actor->id,
            ]);

            $this->audit->log(
                'teacher.payroll_profile_configured',
                $teacher,
                $before,
                $teacher->fresh()->toArray(),
                $actor,
                $request,
                $key
            );

            return $teacher->fresh(['user', 'etablissement.ief.ia']);
        }, 3);
    }

    public function createPeriod(array $data, User $actor, Request $request, ?string $key): PayrollPeriod
    {
        return DB::transaction(function () use ($data, $actor, $request, $key): PayrollPeriod {
            $overlap = PayrollPeriod::query()
                ->whereDate('start_date', '<=', $data['end_date'])
                ->whereDate('end_date', '>=', $data['start_date'])
                ->exists();

            if ($overlap) {
                throw new ConflictHttpException('Cette période chevauche une période de paie existante.');
            }

            $period = PayrollPeriod::create([
                ...$data,
                'status' => PayrollPeriod::STATUS_OPEN,
            ]);
            $this->audit->log('period.created', $period, null, $period->toArray(), $actor, $request, $key);

            return $period;
        }, 3);
    }

    public function saveAttendance(array $data, User $actor, Request $request, ?string $key): PayrollAttendance
    {
        return DB::transaction(function () use ($data, $actor, $request, $key): PayrollAttendance {
            $period = $this->mutablePeriod((int) $data['payroll_period_id']);
            $teacher = $this->teacherForHierarchy($data);
            $existing = PayrollAttendance::query()
                ->where('payroll_period_id', $period->id)
                ->where('enseignant_id', $teacher->id)
                ->lockForUpdate()
                ->first();
            $this->assertVersion($existing, $data['expected_version'] ?? null);

            $deduction = array_key_exists('deduction_amount', $data)
                && $data['deduction_amount'] !== null
                ? round((float) $data['deduction_amount'], 2)
                : round(
                    (
                        ((float) $teacher->salaire_base / max(1, (int) config('payroll.daily_salary_divisor')))
                        * (float) $data['absence_days']
                    ) + (
                        (
                            (float) $teacher->salaire_base
                            / max(1, (int) config('payroll.daily_salary_divisor'))
                            / max(1, (int) config('payroll.workday_minutes'))
                        )
                        * (int) $data['delay_minutes']
                    ),
                    2
                );
            $before = $existing?->toArray();
            $attendance = PayrollAttendance::query()->updateOrCreate(
                [
                    'payroll_period_id' => $period->id,
                    'enseignant_id' => $teacher->id,
                ],
                [
                    'absence_days' => $data['absence_days'],
                    'delay_minutes' => $data['delay_minutes'],
                    'deduction_amount' => $deduction,
                    'notes' => $data['notes'] ?? null,
                    'status' => 'draft',
                    'validated_at' => null,
                    'validated_by' => null,
                    'version' => ($existing?->version ?? 0) + 1,
                ]
            );
            $this->audit->log(
                $before ? 'attendance.updated' : 'attendance.created',
                $attendance,
                $before,
                $attendance->toArray(),
                $actor,
                $request,
                $key
            );

            return $attendance;
        }, 3);
    }

    public function addElement(array $data, User $actor, Request $request, ?string $key): PayrollElement
    {
        return DB::transaction(function () use ($data, $actor, $request, $key): PayrollElement {
            if (in_array($data['code'], PayrollReferenceService::RESERVED_ELEMENT_CODES, true)) {
                throw ValidationException::withMessages([
                    'code' => 'Ce code est calculé automatiquement par le moteur de paie.',
                ]);
            }

            $period = $this->mutablePeriod((int) $data['payroll_period_id']);
            $teacher = $this->teacherForHierarchy($data);
            $existing = PayrollElement::query()
                ->where('payroll_period_id', $period->id)
                ->where('enseignant_id', $teacher->id)
                ->where('code', $data['code'])
                ->lockForUpdate()
                ->first();
            $this->assertVersion($existing, $data['expected_version'] ?? null);
            $before = $existing?->toArray();

            $element = PayrollElement::query()->updateOrCreate(
                [
                    'payroll_period_id' => $period->id,
                    'enseignant_id' => $teacher->id,
                    'code' => $data['code'],
                ],
                [
                    'label' => $data['label'],
                    'category' => $data['category'],
                    'source' => 'manual',
                    'amount' => round((float) $data['amount'], 2),
                    'is_exempt' => false,
                    'exemption_reason' => null,
                    'status' => 'draft',
                    'created_by' => $actor->id,
                    'validated_by' => null,
                    'validated_at' => null,
                    'version' => ($existing?->version ?? 0) + 1,
                ]
            );
            $this->audit->log(
                $before ? 'element.updated' : 'element.created',
                $element,
                $before,
                $element->toArray(),
                $actor,
                $request,
                $key
            );

            return $element;
        }, 3);
    }

    /**
     * Applique une avance ou une retenue Tabaski à tout un groupe.
     *
     * Le code, le libellé et la catégorie viennent du contrôleur et non du
     * navigateur : un utilisateur ne peut donc pas transformer une retenue en
     * gain. Chaque ligne créée reste reliée au même lot d'application.
     *
     * @return array<string, mixed>
     */
    public function applyCollectiveTabaski(
        array $data,
        string $code,
        string $label,
        string $category,
        User $actor,
        Request $request,
        ?string $key
    ): array {
        return DB::transaction(function () use (
            $data,
            $code,
            $label,
            $category,
            $actor,
            $request,
            $key
        ): array {
            $academicYear = DB::table('annee_academiques')
                ->where('id', (int) $data['annee_academique_id'])
                ->first();
            $corps = DB::table('corps_enseignant')
                ->where('id', (int) $data['corps_id'])
                ->whereIn('code', ['VAC', 'PC'])
                ->first();

            if (! $academicYear || ! $corps) {
                throw ValidationException::withMessages([
                    'corps_id' => 'Le corps ou l’année académique sélectionné(e) n’est plus disponible.',
                ]);
            }

            $iaIds = collect($data['ia_ids'])->map(fn (mixed $id): int => (int) $id)->unique()->sort()->values();
            $months = $code === 'TABASKI_RETENUE'
                ? collect($data['months'])->map(fn (mixed $month): int => (int) $month)->values()
                : collect([(int) $data['month']]);
            $periods = $this->periodsForAcademicMonths($academicYear, $months, $code === 'TABASKI_RETENUE' ? 'months' : 'month');

            $activeColumn = Schema::hasColumn('enseignants', 'actif') ? 'actif' : 'est_actif';
            $corpsColumn = Schema::hasColumn('enseignants', 'corps_id') ? 'corps_id' : 'corps_enseignant_id';
            $teachersQuery = Enseignant::query()
                ->with('user')
                ->where($activeColumn, true)
                ->where($corpsColumn, (int) $corps->id)
                ->where(function ($query) use ($iaIds): void {
                    if (Schema::hasColumn('enseignants', 'ia_id')) {
                        $query->whereIn('ia_id', $iaIds->all());
                    }

                    if (Schema::hasTable('etablissements')) {
                        $method = Schema::hasColumn('enseignants', 'ia_id') ? 'orWhereHas' : 'whereHas';
                        $query->{$method}('etablissement.ief', fn ($iefQuery) => $iefQuery->whereIn('iefs.ia_id', $iaIds->all()));
                    }
                });

            if (Schema::hasTable('etablissements')) {
                $teachersQuery->with('etablissement.ief');
            }

            $teachers = $teachersQuery
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            if ($teachers->isEmpty()) {
                throw ValidationException::withMessages([
                    'corps_id' => 'Aucun enseignant actif de ce corps ne correspond aux IA sélectionnées.',
                ]);
            }

            $applicationReference = sprintf(
                '%s-AY%d-%s-IA%s-M%s',
                $code,
                (int) $academicYear->id,
                strtoupper((string) $corps->code),
                substr(hash('sha256', $iaIds->implode(',')), 0, 10),
                $months->map(fn (int $month): string => str_pad((string) $month, 2, '0', STR_PAD_LEFT))->implode('-')
            );
            $created = 0;
            $updated = 0;

            foreach ($periods as $period) {
                foreach ($teachers as $teacher) {
                    $element = PayrollElement::query()
                        ->where('payroll_period_id', $period->id)
                        ->where('enseignant_id', $teacher->id)
                        ->where('code', $code)
                        ->lockForUpdate()
                        ->first();
                    $element ? $updated++ : $created++;

                    PayrollElement::query()->updateOrCreate(
                        [
                            'payroll_period_id' => $period->id,
                            'enseignant_id' => $teacher->id,
                            'code' => $code,
                        ],
                        [
                            'label' => $label,
                            'category' => $category,
                            'source' => 'manual',
                            'amount' => round((float) $data['amount'], 2),
                            'academic_year' => $academicYear->libelle,
                            'annee_academique_id' => (int) $academicYear->id,
                            'application_scope' => 'collective',
                            'application_reference' => $applicationReference,
                            'application_corps_id' => (int) $corps->id,
                            'application_ia_id' => $this->teacherIaId($teacher),
                            'application_ief_id' => $this->teacherIefId($teacher),
                            'applied_at' => now(),
                            'applied_by' => $actor->id,
                            'is_exempt' => false,
                            'exemption_reason' => null,
                            // Le montant saisi est conservé tel quel pour chaque mois choisi.
                            'status' => 'validated',
                            'created_by' => $actor->id,
                            'validated_by' => $actor->id,
                            'validated_at' => now(),
                            'version' => ($element?->version ?? 0) + 1,
                        ]
                    );
                }
            }

            $summary = [
                'application_reference' => $applicationReference,
                'payroll_period_ids' => $periods->pluck('id')->all(),
                'months' => $months->all(),
                'academic_year' => $academicYear->libelle,
                'annee_academique_id' => (int) $academicYear->id,
                'corps_id' => (int) $corps->id,
                'corps_code' => (string) $corps->code,
                'ia_ids' => $iaIds->all(),
                'amount' => round((float) $data['amount'], 2),
                'affected_teachers' => $teachers->count(),
                'created_elements' => $created,
                'updated_elements' => $updated,
            ];
            $this->audit->log(
                'tabaski.collective_applied',
                $periods->first(),
                null,
                $summary,
                $actor,
                $request,
                $key
            );

            return $summary;
        }, 3);
    }

    private function teacherForHierarchy(array $data): Enseignant
    {
        $teacher = Enseignant::query()
            ->with('etablissement.ief')
            ->where('actif', true)
            ->findOrFail($data['enseignant_id']);
        $this->assertTeacherHierarchy($teacher, $data);

        return $teacher;
    }

    private function assertTeacherHierarchy(Enseignant $teacher, array $data): void
    {
        $teacher->loadMissing('etablissement.ief');
        $inspection = $teacher->etablissement?->ief;

        if (
            ! $inspection
            || mb_strtoupper(trim((string) $teacher->matricule)) !== mb_strtoupper(trim((string) $data['matricule']))
            || (int) $inspection->id !== (int) $data['ief_id']
            || (int) $inspection->ia_id !== (int) $data['ia_id']
        ) {
            throw ValidationException::withMessages([
                'enseignant_id' => 'Ce matricule ne correspond pas à l’IA et à l’IEF sélectionnées.',
            ]);
        }
    }

    public function exemptElement(array $data, User $actor, Request $request, ?string $key): PayrollElement
    {
        return DB::transaction(function () use ($data, $actor, $request, $key): PayrollElement {
            $element = PayrollElement::query()->lockForUpdate()->findOrFail($data['payroll_element_id']);
            $this->mutablePeriod($element->payroll_period_id);
            $this->assertVersion($element, (int) $data['expected_version']);
            $before = $element->toArray();
            $element->update([
                'is_exempt' => true,
                'exemption_reason' => $data['reason'],
                'version' => $element->version + 1,
            ]);
            $this->audit->log('element.exempted', $element, $before, $element->toArray(), $actor, $request, $key);

            return $element;
        }, 3);
    }

    public function validateInputs(
        int $periodId,
        string $type,
        User $actor,
        Request $request,
        ?string $key
    ): PayrollPeriod {
        return DB::transaction(function () use ($periodId, $type, $actor, $request, $key): PayrollPeriod {
            $period = $this->mutablePeriod($periodId);
            $query = $type === 'attendance' ? $period->attendances() : $period->elements();
            $count = $query->where('status', 'draft')->update([
                'status' => 'validated',
                'validated_by' => $actor->id,
                'validated_at' => now(),
                'version' => DB::raw('version + 1'),
                'updated_at' => now(),
            ]);
            $this->audit->log(
                "{$type}.validated",
                $period,
                null,
                ['validated_records' => $count],
                $actor,
                $request,
                $key
            );

            return $period->fresh();
        }, 3);
    }

    public function calculate(int $periodId, User $actor, Request $request, ?string $key): PayrollRun
    {
        return DB::transaction(function () use ($periodId, $actor, $request, $key): PayrollRun {
            $period = PayrollPeriod::findOrFail($periodId);
            $run = $this->calculation->calculate($period, $actor);
            $this->audit->log('payroll.calculated', $run, null, $run->toArray(), $actor, $request, $key);

            return $run;
        }, 3);
    }

    public function validatePayroll(int $periodId, User $actor, Request $request, ?string $key): PayrollPeriod
    {
        return DB::transaction(function () use ($periodId, $actor, $request, $key): PayrollPeriod {
            $period = PayrollPeriod::query()->lockForUpdate()->findOrFail($periodId);
            $run = PayrollRun::query()->where('payroll_period_id', $period->id)->lockForUpdate()->first();

            if ($period->status !== PayrollPeriod::STATUS_CALCULATED || ! $run) {
                throw new ConflictHttpException('La paie doit être calculée avant sa validation.');
            }

            if (! hash_equals((string) $period->checksum, (string) $run->checksum)) {
                throw new ConflictHttpException('Le contrôle d’intégrité du calcul a échoué.');
            }

            $before = $period->toArray();
            $run->update([
                'status' => 'validated',
                'validated_by' => $actor->id,
                'validated_at' => now(),
            ]);
            $period->update([
                'status' => PayrollPeriod::STATUS_VALIDATED,
                'validated_by' => $actor->id,
                'validated_at' => now(),
                'version' => $period->version + 1,
            ]);
            $this->audit->log('payroll.validated', $period, $before, $period->toArray(), $actor, $request, $key);

            return $period->fresh();
        }, 3);
    }

    public function closePeriod(array $data, User $actor, Request $request, ?string $key): PayrollPeriod
    {
        return DB::transaction(function () use ($data, $actor, $request, $key): PayrollPeriod {
            $period = PayrollPeriod::query()->lockForUpdate()->findOrFail($data['payroll_period_id']);
            $this->assertVersion($period, (int) $data['expected_version']);

            if ($period->status !== PayrollPeriod::STATUS_VALIDATED) {
                throw new ConflictHttpException('Seule une période validée peut être clôturée.');
            }

            if (! hash_equals($period->code, trim((string) $data['confirmation']))) {
                throw new ConflictHttpException('Le code de confirmation ne correspond pas à la période.');
            }

            $run = PayrollRun::query()
                ->where('payroll_period_id', $period->id)
                ->where('status', 'validated')
                ->lockForUpdate()
                ->first();
            if (! $run) {
                throw new ConflictHttpException('Le cycle de paie validé est introuvable.');
            }

            $before = $period->toArray();
            $run->update(['status' => 'closed']);
            $period->update([
                'status' => PayrollPeriod::STATUS_CLOSED,
                'closed_by' => $actor->id,
                'closed_at' => now(),
                'version' => $period->version + 1,
            ]);
            $this->audit->log('period.closed', $period, $before, $period->toArray(), $actor, $request, $key);

            return $period->fresh();
        }, 3);
    }

    public function markPaid(array $data, User $actor, Request $request, ?string $key): PayrollPayslip
    {
        return DB::transaction(function () use ($data, $actor, $request, $key): PayrollPayslip {
            $payslip = PayrollPayslip::query()->lockForUpdate()->findOrFail($data['payroll_payslip_id']);
            $this->assertVersion($payslip, (int) $data['expected_version']);
            $period = PayrollPeriod::findOrFail($payslip->payroll_period_id);

            if (! in_array($period->status, [
                PayrollPeriod::STATUS_VALIDATED,
                PayrollPeriod::STATUS_CLOSED,
            ], true)) {
                throw new ConflictHttpException('La paie doit être validée avant le marquage du paiement.');
            }

            if ($payslip->payment_status === 'paid') {
                throw new ConflictHttpException('Ce bulletin est déjà marqué comme payé.');
            }

            $before = $payslip->toArray();
            $payslip->update([
                'payment_status' => 'paid',
                'payment_reference' => $data['payment_reference'],
                'paid_at' => now(),
                'version' => $payslip->version + 1,
            ]);
            $this->audit->log('payslip.paid', $payslip, $before, $payslip->toArray(), $actor, $request, $key);

            return $payslip->fresh();
        }, 3);
    }

    private function mutablePeriod(int $periodId): PayrollPeriod
    {
        $period = PayrollPeriod::query()->lockForUpdate()->findOrFail($periodId);
        if (! $period->isMutable()) {
            throw new ConflictHttpException(
                'Cette période n’est plus modifiable. Créez une régularisation sur une période ouverte.'
            );
        }

        return $period;
    }

    /** @return Collection<int, PayrollPeriod> */
    private function periodsForAcademicMonths(object $academicYear, Collection $months, string $field): Collection
    {
        $wanted = $months->map(fn (mixed $month): int => (int) $month)->unique()->values();
        $codesByMonth = collect();
        $cursor = CarbonImmutable::parse($academicYear->date_debut)->startOfMonth();
        $end = CarbonImmutable::parse($academicYear->date_fin)->endOfMonth();

        while ($cursor->lessThanOrEqualTo($end)) {
            if ($wanted->contains($cursor->month) && ! $codesByMonth->has($cursor->month)) {
                $codesByMonth->put($cursor->month, $cursor->format('Y-m'));
            }
            $cursor = $cursor->addMonth();
        }

        if ($codesByMonth->count() !== $wanted->count()) {
            throw ValidationException::withMessages([
                $field => 'Un ou plusieurs mois ne font pas partie de l’année académique sélectionnée.',
            ]);
        }

        $periodsByCode = PayrollPeriod::query()
            ->whereIn('code', $codesByMonth->values()->all())
            ->get()
            ->keyBy('code');
        $missing = $codesByMonth->values()->reject(fn (string $code): bool => $periodsByCode->has($code));

        if ($missing->isNotEmpty()) {
            throw ValidationException::withMessages([
                $field => 'Créez d’abord les périodes de paie suivantes : '.$missing->implode(', ').'.',
            ]);
        }

        return $wanted->map(function (int $month) use ($codesByMonth, $periodsByCode): PayrollPeriod {
            return $this->mutablePeriod((int) $periodsByCode->get($codesByMonth->get($month))->id);
        })->values();
    }

    private function teacherIaId(Enseignant $teacher): ?int
    {
        $id = $teacher->getAttribute('ia_id');
        if ($id === null && $teacher->relationLoaded('etablissement')) {
            $id = $teacher->etablissement?->ief?->ia_id;
        }

        return $id === null ? null : (int) $id;
    }

    private function teacherIefId(Enseignant $teacher): ?int
    {
        $id = $teacher->getAttribute('ief_id');
        if ($id === null && $teacher->relationLoaded('etablissement')) {
            $id = $teacher->etablissement?->ief_id;
        }

        return $id === null ? null : (int) $id;
    }

    private function assertVersion(?Model $model, ?int $expectedVersion): void
    {
        if ($model && ($expectedVersion === null || (int) $model->version !== $expectedVersion)) {
            throw new ConflictHttpException(
                'Cette donnée a été modifiée par un autre utilisateur. Rechargez la page.'
            );
        }
    }
}
