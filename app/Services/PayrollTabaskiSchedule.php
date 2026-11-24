<?php

namespace App\Services;

use App\Models\Admin\User;
use App\Models\Enseignant;
use App\Models\PayrollElement;
use App\Models\PayrollPeriod;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/** Fixed beneficiaries and deferred installments; never opens a payroll period. */
class PayrollTabaskiSchedule
{
    /** An attribution alone is not a received advance: require its bulletin line. */
    public function receivedAdvances(): \Illuminate\Database\Query\Builder
    {
        return DB::table('payroll_tabaski_allocations')->whereNotNull('advance_period_id')
            ->whereExists(function ($q) {
                $q->selectRaw('1')->from('payroll_payslips as receipt')
                    ->join('payroll_payslip_lines as receipt_line', 'receipt_line.payroll_payslip_id', '=', 'receipt.id')
                    ->whereColumn('receipt.enseignant_id', 'payroll_tabaski_allocations.enseignant_id')
                    ->whereColumn('receipt.payroll_period_id', 'payroll_tabaski_allocations.advance_period_id')
                    ->where('receipt_line.code', 'TABASKI_AVANCE')->where('receipt_line.category', 'earning')
                    ->whereColumn('receipt_line.amount', 'payroll_tabaski_allocations.advance_amount');
            });
    }

    public function allowsDeduction(PayrollElement $element): bool
    {
        return $this->receivedAdvances()->where('enseignant_id', $element->enseignant_id)
            ->where('batch_reference', $element->application_reference)->whereNotNull('repayment_from_month')
            ->whereExists(function ($q) use ($element) {
                $q->selectRaw('1')->from('payroll_tabaski_installments as installment')
                    ->whereColumn('installment.allocation_id', 'payroll_tabaski_allocations.id')
                    ->where('installment.payroll_period_id', $element->payroll_period_id)
                    ->where('installment.amount', $element->amount);
            })->exists();
    }

    public function apply(array $data, bool $deduction, User $actor, Request $request, ?string $key): array
    {
        return DB::transaction(function () use ($data, $deduction, $actor, $request, $key) {
            // Same lock order as period opening: a campaign cannot arrive halfway through calculation.
            DB::table('annee_academiques')->orderBy('id')->lockForUpdate()->get();
            if (! empty($data['ief_id']) && ! DB::table('iefs')->where('id', $data['ief_id'])->whereIn('ia_id', $data['ia_ids'])->exists()) {
                throw ValidationException::withMessages(['ief_id' => 'Cette IEF ne dépend pas des IA sélectionnées.']);
            }
            $teacherId = null;
            if (trim((string) ($data['matricule'] ?? '')) !== '') {
                $matches = Enseignant::whereNull('deleted_at')->whereRaw('UPPER(TRIM(matricule)) = ?', [mb_strtoupper(trim($data['matricule']))])->pluck('id');
                if ($matches->count() !== 1) throw ValidationException::withMessages(['matricule' => 'Aucun formateur unique ne correspond à ce matricule exact.']);
                $teacherId = $matches->first();
            }
            $from = CarbonImmutable::createFromFormat('!Y-m', $data['start_month']);
            $batch = 'TAB-'.Str::uuid();
            $rows = collect();
            $skipped = 0;
            if (! $deduction) {
                $teachers = Enseignant::where('est_actif', true)->whereNull('deleted_at')
                    ->where('statut_enseignant_id', $data['statut_enseignant_id'])
                    ->where(function ($q) use ($data) {
                        $q->whereIn('ia_id', $data['ia_ids'])->orWhere(function ($fallback) use ($data) {
                            $fallback->whereNull('ia_id')->whereIn('ief_id', DB::table('iefs')->whereIn('ia_id', $data['ia_ids'])->select('id'));
                        });
                    })
                    ->when(! empty($data['ief_id']), fn ($q) => $q->where('ief_id', $data['ief_id']))
                    ->when($teacherId !== null, fn ($q) => $q->where('id', $teacherId))
                    ->orderBy('id')->lockForUpdate()->get();
                foreach ($teachers as $teacher) {
                    $old = DB::table('payroll_tabaski_allocations')->where('enseignant_id', $teacher->id)->get();
                    $hasOutstanding = $old->contains(fn ($a) => DB::table('payroll_tabaski_installments')->where('allocation_id', $a->id)->count() < 10);
                    if ($hasOutstanding || $old->contains(fn ($a) => $a->advance_from_month === $from->toDateString())) { $skipped++; continue; }
                    $ia = $teacher->ia_id ?: DB::table('iefs')->where('id', $teacher->ief_id)->value('ia_id');
                    $id = DB::table('payroll_tabaski_allocations')->insertGetId([
                        'enseignant_id' => $teacher->id, 'batch_reference' => $batch,
                        'statut_enseignant_id' => $teacher->statut_enseignant_id, 'ia_id' => $ia, 'ief_id' => $teacher->ief_id,
                        'advance_from_month' => $from->toDateString(), 'advance_amount' => 100000, 'monthly_amount' => 10000,
                        'created_by' => $actor->id, 'created_at' => now(), 'updated_at' => now(),
                    ]);
                    $rows->push($id);
                }
            } else {
                // Select frozen beneficiaries, never today's complete Personnel list.
                $allocations = $this->receivedAdvances()
                    ->where('statut_enseignant_id', $data['statut_enseignant_id'])->whereIn('ia_id', $data['ia_ids'])
                    ->when(! empty($data['ief_id']), fn ($q) => $q->where('ief_id', $data['ief_id']))
                    ->when($teacherId !== null, fn ($q) => $q->where('enseignant_id', $teacherId))
                    ->whereNull('repayment_from_month')->orderBy('id')->lockForUpdate()->get();
                foreach ($allocations as $allocation) {
                    if (DB::table('payroll_tabaski_installments')->where('allocation_id', $allocation->id)->count() >= 10) continue;
                    $advanceMonth = $allocation->advance_period_id
                        ? PayrollPeriod::findOrFail($allocation->advance_period_id)->start_date->toDateString() : $allocation->advance_from_month;
                    $minimum = CarbonImmutable::parse($advanceMonth)->startOfMonth()->addMonth();
                    $repayment = $from->greaterThan($minimum) ? $from : $minimum;
                    DB::table('payroll_tabaski_allocations')->where('id', $allocation->id)->update([
                        'repayment_from_month' => $repayment->toDateString(), 'activated_by' => $actor->id, 'activated_at' => now(), 'updated_at' => now(),
                    ]);
                    $rows->push($allocation->id);
                }
            }
            if ($rows->isEmpty()) {
                throw ValidationException::withMessages(['selection' => $deduction
                    ? 'Aucun bénéficiaire éligible : la retenue exige une avance Tabaski déjà portée sur un bulletin et dont le remboursement n’est pas encore activé. Une avance seulement programmée ne suffit pas.'
                    : 'Aucun nouvel enseignant éligible : les avances en cours ou déjà attribuées ne sont pas dupliquées.']);
            }
            $updated = app(PayrollTabaskiBulletinUpdater::class)->update($rows->all(), $deduction, $actor);
            $summary = ['affected_teachers' => $rows->count(), 'skipped_teachers' => $skipped, 'allocation_ids' => $rows->all(),
                'start_month' => $from->format('Y-m'), 'amount' => $deduction ? 10000 : 100000, 'installments' => $deduction ? 10 : 1,
                'ief_id' => $data['ief_id'] ?? null, 'matricule' => $data['matricule'] ?? null];
            $summary['updated_bulletins'] = $updated;
            // A model wrapper gives the existing audit logger an identifiable subject.
            $subject = new \App\Models\PayrollTabaskiAllocation();
            $subject->setAttribute('id', $rows->first());
            app(PayrollAuditService::class)->log($deduction ? 'tabaski.repayment_activated' : 'tabaski.advance_scheduled', $subject, null, $summary, $actor, $request, $key);
            return $summary;
        }, 3);
    }

    /** Called inside the opening transaction, before the national calculation. */
    public function materialize(PayrollPeriod $period, array $teacherIds, User $actor, ?array $allocationIds = null): void
    {
        $month = $period->start_date->copy()->startOfMonth()->toDateString();
        $allocations = DB::table('payroll_tabaski_allocations')->whereIn('enseignant_id', $teacherIds)
            ->when($allocationIds !== null, fn ($q) => $q->whereIn('id', $allocationIds))
            ->whereDate('advance_from_month', '<=', $month)->orderBy('id')->lockForUpdate()->get();
        foreach ($allocations as $allocation) {
            if (! $allocation->advance_period_id) {
                if ($allocation->advance_from_month !== $month) continue;
                $this->element($period, $allocation, 'TABASKI_AVANCE', 'Avance Tabaski', 'earning', 100000, $actor);
                DB::table('payroll_tabaski_allocations')->where('id', $allocation->id)->update(['advance_period_id' => $period->id, 'updated_at' => now()]);
                continue; // Never recover an advance on the same bulletin as its payment.
            }
            if ((int) $allocation->advance_period_id === (int) $period->id || ! $allocation->repayment_from_month || $allocation->repayment_from_month > $month) continue;
            if (! $this->receivedAdvances()->where('id', $allocation->id)->exists()) continue;
            $advancePeriod = PayrollPeriod::findOrFail($allocation->advance_period_id);
            if ($advancePeriod->start_date->format('Y-m') >= $period->start_date->format('Y-m')) continue;
            $installments = DB::table('payroll_tabaski_installments')->where('allocation_id', $allocation->id);
            if ((clone $installments)->where('payroll_period_id', $period->id)->exists() || (clone $installments)->count() >= 10) continue;
            $sequence = (int) CarbonImmutable::parse($allocation->repayment_from_month)->diffInMonths(CarbonImmutable::parse($month)) + 1;
            if ($sequence < 1 || $sequence > 10) continue;
            $this->element($period, $allocation, 'TABASKI_RETENUE', 'Retenue Tabaski', 'deduction', 10000, $actor);
            DB::table('payroll_tabaski_installments')->insert(['allocation_id' => $allocation->id, 'payroll_period_id' => $period->id,
                'sequence' => $sequence, 'amount' => 10000, 'created_at' => now(), 'updated_at' => now()]);
        }
    }

    private function element(PayrollPeriod $period, object $allocation, string $code, string $label, string $category, int $amount, User $actor): void
    {
        if (PayrollElement::where('payroll_period_id', $period->id)->where('enseignant_id', $allocation->enseignant_id)->where('code', $code)->exists()) {
            throw ValidationException::withMessages(['tabaski' => 'Une ligne Tabaski existe déjà pour cet enseignant et ce mois. Vérifiez le doublon avant l’ouverture.']);
        }
        PayrollElement::create(['payroll_period_id' => $period->id, 'enseignant_id' => $allocation->enseignant_id,
            'code' => $code, 'label' => $label, 'category' => $category, 'source' => 'system', 'amount' => $amount,
            'status' => 'validated', 'is_exempt' => false, 'created_by' => $actor->id, 'validated_by' => $actor->id, 'validated_at' => now(),
            'application_scope' => 'collective', 'application_reference' => $allocation->batch_reference,
            'application_statut_enseignant_id' => $allocation->statut_enseignant_id, 'application_ia_id' => $allocation->ia_id,
            'application_ief_id' => $allocation->ief_id, 'applied_by' => $actor->id, 'applied_at' => now()]);
    }
}
