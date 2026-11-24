<?php

namespace App\Services;

use App\Models\Admin\User;
use App\Models\PayrollPayslip;
use App\Models\PayrollPeriod;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** Apply only Tabaski deltas to salary statements that already exist. */
class PayrollTabaskiBulletinUpdater
{
    public function update(array $allocationIds, bool $deduction, User $actor): int
    {
        $allocations = DB::table('payroll_tabaski_allocations')->whereIn('id', $allocationIds)->get();
        $updated = 0;
        foreach (PayrollPeriod::orderBy('start_date')->lockForUpdate()->get() as $period) {
            $month = $period->start_date->copy()->startOfMonth()->toDateString();
            $targets = $allocations->filter(function ($a) use ($month, $deduction) {
                if (! $deduction) return $a->advance_from_month === $month;
                return $a->advance_period_id && $a->repayment_from_month && $month >= $a->repayment_from_month
                    && $month <= CarbonImmutable::parse($a->repayment_from_month)->addMonths(9)->toDateString();
            });
            $changed = false;
            foreach ($targets as $target) {
                $slip = PayrollPayslip::where('payroll_period_id', $period->id)->where('enseignant_id', $target->enseignant_id)->lockForUpdate()->first();
                if (! $slip) continue; // No statement entry: do not fabricate a bulletin.
                if ($slip->payment_status === 'paid') {
                    throw ValidationException::withMessages(['start_month' => 'Un bulletin du mois choisi est déjà payé. Aucune opération Tabaski n’a été enregistrée ; choisissez un autre mois pour ce bénéficiaire.']);
                }
                $code = $deduction ? 'TABASKI_RETENUE' : 'TABASKI_AVANCE';
                if ($slip->lines()->where('code', $code)->exists()) {
                    throw ValidationException::withMessages(['start_month' => 'Ce bulletin contient déjà une ligne Tabaski de ce type. Vérifiez son historique.']);
                }
                app(PayrollTabaskiSchedule::class)->materialize($period, [$target->enseignant_id], $actor, [$target->id]);
                $element = $period->elements()->where('enseignant_id', $target->enseignant_id)->where('code', $code)->first();
                if (! $element) continue;
                $line = ['code' => $code, 'label' => $element->label, 'category' => $element->category,
                    'amount' => $element->amount, 'source' => 'system', 'sort_order' => 80];
                $slip->lines()->create($line);
                $profile = $slip->profile_snapshot ?? [];
                // Freeze only the new line's presentation; retain every old setting.
                $profile['bulletin_display'] = array_replace($profile['bulletin_display'] ?? app(PayrollPayslipPresentationService::class)->snapshot($slip->lines()->get()->toArray()),
                    app(PayrollPayslipPresentationService::class)->snapshot([$line]));
                $amount = (float) $element->amount;
                $slip->update(['profile_snapshot' => $profile, 'gross_amount' => (float) $slip->gross_amount + ($deduction ? 0 : $amount),
                    'deduction_amount' => (float) $slip->deduction_amount + ($deduction ? $amount : 0),
                    'net_amount' => (float) $slip->net_amount + ($deduction ? -$amount : $amount), 'version' => $slip->version + 1]);
                $updated++; $changed = true;
            }
            if ($changed) $this->totals($period);
        }
        return $updated;
    }

    private function totals(PayrollPeriod $period): void
    {
        $run = $period->run()->lockForUpdate()->firstOrFail();
        $slips = $run->payslips()->with('lines')->orderBy('enseignant_id')->get();
        $checksum = hash('sha256', $slips->map(fn ($slip) => $slip->reference.':'.hash('sha256', json_encode([
            'profile' => $slip->profile_snapshot,
            'lines' => $slip->lines->map(fn ($line) => $line->only(['code','label','category','amount','source','sort_order']))->all(),
        ], JSON_THROW_ON_ERROR)))->implode('|'));
        $totals = ['employee_count' => $slips->count(), 'total_gross' => round((float) $slips->sum('gross_amount'),2),
            'total_deductions' => round((float) $slips->sum('deduction_amount'),2), 'total_net' => round((float) $slips->sum('net_amount'),2), 'checksum' => $checksum];
        $run->update($totals + ['total_employer_contributions' => round((float) $slips->sum('employer_contribution_amount'),2)]);
        $period->update($totals + ['version' => $period->version + 1]);
    }
}
