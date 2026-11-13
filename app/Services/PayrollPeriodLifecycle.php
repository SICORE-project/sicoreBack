<?php

namespace App\Services;

use App\Models\Admin\User;
use App\Models\Enseignant;
use App\Models\Paie\PeriodePaie;
use App\Models\PayrollPeriod;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** National monthly lifecycle, owned exclusively by the period creation workflow. */
class PayrollPeriodLifecycle
{
    public function date(object $reference): CarbonImmutable
    {
        $month = app(PayrollPeriodCatalogue::class)->month($reference);
        $explicit = preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $reference->code) === 1;
        $years = DB::table('annee_academiques')->where('est_cloturee', false)->get();
        if (! $explicit && $years->where('est_active', true)->count() === 1) {
            $years = $years->where('est_active', true);
        }
        $dates = collect();
        foreach ($years as $year) {
            $date = CarbonImmutable::parse($year->date_debut)->startOfMonth();
            while ($date->lessThanOrEqualTo(CarbonImmutable::parse($year->date_fin)->endOfMonth())) {
                if ($date->month === $month && (! $explicit || $date->format('Y-m') === $reference->code)) $dates->push($date);
                $date = $date->addMonth();
            }
        }
        $dates = $dates->unique(fn ($date) => $date->format('Y-m'));
        if ($dates->count() !== 1) {
            throw ValidationException::withMessages(['code' => 'Le mois doit correspondre à une année académique non clôturée unique. Utilisez un code AAAA-MM en cas d’ambiguïté.']);
        }
        return $dates->first();
    }

    public function open(PeriodePaie $reference, User $actor, Request $request): PayrollPeriod
    {
        return DB::transaction(function () use ($reference, $actor, $request) {
            // Shared lock serializes competing openings, including different months.
            DB::table('annee_academiques')->orderBy('id')->lockForUpdate()->get();
            $active = PayrollPeriod::where('status', '!=', PayrollPeriod::STATUS_CLOSED)->lockForUpdate()->get();
            if ($active->isNotEmpty()) {
                throw ValidationException::withMessages(['code' => 'Clôturez d’abord les périodes en cours : '.$active->pluck('label')->implode(', ').'.']);
            }
            $date = $this->date($reference);
            $code = $date->format('Y-m');
            if (PayrollPeriod::where('code', $code)->exists()) {
                throw ValidationException::withMessages(['code' => 'Cette période a déjà été traitée. Son historique reste consultable ; elle ne peut pas être rouverte.']);
            }
            $label = preg_match('/\b'.$date->year.'\b/', $reference->libelle) ? $reference->libelle : $reference->libelle.' '.$date->year;
            $period = PayrollPeriod::create(['code' => $code, 'label' => $label,
                'start_date' => $date->toDateString(), 'end_date' => $date->endOfMonth()->toDateString(), 'status' => 'open']);
            $statuses = DB::table('statuts_enseignant')->whereIn('code', ['PC', 'VAC'])
                ->orWhereRaw('LOWER(libelle) LIKE ?', ['%contractuel%'])->orWhereRaw('LOWER(libelle) LIKE ?', ['%vacataire%'])->pluck('id');
            $ids = Enseignant::where('est_actif', true)->whereNull('deleted_at')
                ->where(function ($q) use ($statuses) { $q->whereIn('statut_enseignant_id', $statuses)->orWhereIn('type_engagement', ['contractuel', 'vacataire']); })
                ->orderBy('id')->pluck('id')->all();
            if ($ids === []) throw ValidationException::withMessages(['code' => 'Aucun enseignant PC/VAC actif disponible pour le calcul national.']);
            $run = app(PayrollCalculationService::class)->calculate($period, $actor, $ids);
            app(PayrollAuditService::class)->log('period.opened_and_calculated', $period, null, $run->toArray(), $actor, $request, null);
            return $period->fresh();
        }, 3);
    }

    public function close(int $id, User $actor, Request $request): PayrollPeriod
    {
        return DB::transaction(function () use ($id, $actor, $request) {
            DB::table('annee_academiques')->orderBy('id')->lockForUpdate()->get();
            $period = PayrollPeriod::lockForUpdate()->findOrFail($id);
            if ($period->status === 'closed') return $period;
            // Empty legacy periods can be closed without creating fictitious payroll.
            if ($period->status === 'open' && ! $period->run()->exists() && ! $period->payslips()->exists()) {
                if ($period->elements()->exists() || $period->attendances()->exists()) {
                    throw ValidationException::withMessages(['periode' => 'Cette période contient des saisies non calculées ; sa clôture nécessite leur régularisation.']);
                }
                $before = $period->toArray();
                $period->update(['status' => 'closed', 'closed_by' => $actor->id, 'closed_at' => now(), 'version' => $period->version + 1]);
                app(PayrollAuditService::class)->log('period.empty_closed', $period, $before, $period->toArray(), $actor, $request, null);
                return $period->fresh();
            }
            $actions = app(PayrollActionService::class);
            if ($period->status === 'calculated') $period = $actions->validatePayroll($period->id, $actor, $request, null);
            return $actions->closePeriod(['payroll_period_id' => $period->id, 'expected_version' => $period->version, 'confirmation' => $period->code], $actor, $request, null);
        }, 3);
    }
}
