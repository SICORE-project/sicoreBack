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
        if (! empty($reference->date_debut) && ! empty($reference->date_fin)) {
            $start = CarbonImmutable::parse($reference->date_debut);
            $end = CarbonImmutable::parse($reference->date_fin);
            $month = app(PayrollPeriodCatalogue::class)->month($reference);
            if ($start->greaterThan($end) || $start->format('Y-m') !== $end->format('Y-m') || $month !== $start->month
                || (preg_match('/^\d{4}-\d{2}$/', $reference->code) && $reference->code !== $start->format('Y-m'))) {
                throw ValidationException::withMessages(['date_fin' => 'Les dates doivent appartenir au même mois et correspondre au code de la période.']);
            }
            if (! DB::table('annee_academiques')->whereDate('date_debut', '<=', $start->toDateString())->whereDate('date_fin', '>=', $end->toDateString())->exists()) {
                throw ValidationException::withMessages(['date_debut' => 'Les dates doivent être comprises dans une année académique enregistrée.']);
            }
            return $start;
        }
        $month = app(PayrollPeriodCatalogue::class)->month($reference);
        if ($month === null) {
            throw ValidationException::withMessages(['code' => 'Mois non reconnu. Renseignez un mois de janvier à décembre (exemple : JUILLET) ou un code AAAA-MM.']);
        }
        $explicit = preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $reference->code) === 1;
        // Academic status is metadata, never a payroll authorization condition.
        $years = DB::table('annee_academiques')->get();
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
            throw ValidationException::withMessages(['code' => $dates->isEmpty()
                ? 'Aucune année académique enregistrée ne couvre ce mois. Vérifiez les dates des années académiques et le code AAAA-MM.'
                : 'Ce mois correspond à plusieurs années. Précisez le code AAAA-MM (exemple : 2027-07). Les statuts actif ou clôturé ne bloquent pas la paie.']);
        }
        return $dates->first();
    }

    public function open(PeriodePaie $reference, User $actor, Request $request): PayrollPeriod
    {
        return DB::transaction(function () use ($reference, $actor, $request) {
            // Shared lock serializes competing openings, including different months.
            DB::table('annee_academiques')->orderBy('id')->lockForUpdate()->get();
            $date = $this->date($reference);
            $code = $date->format('Y-m');
            $existing = PayrollPeriod::where('code', $code)->lockForUpdate()->first();
            if ($existing) {
                // A catalogue alias must never recalculate or reopen historical payroll.
                return $existing;
            }
            // Like the main catalogue, months can be registered in any order.
            // A future reference must not prematurely freeze another month's payroll.
            $label = preg_match('/\b'.$date->year.'\b/', $reference->libelle) ? $reference->libelle : $reference->libelle.' '.$date->year;
            $period = PayrollPeriod::create(['code' => $code, 'label' => $label,
                'start_date' => $date->toDateString(), 'end_date' => $reference->date_fin ?: $date->endOfMonth()->toDateString(), 'status' => 'open']);
            $reference->forceFill(['payroll_period_id' => $period->id,
                'date_debut' => $date->toDateString(), 'date_fin' => $period->end_date->toDateString()])->save();
            $statuses = DB::table('statuts_enseignant')->whereIn('code', ['PC', 'VAC'])
                ->orWhereRaw('LOWER(libelle) LIKE ?', ['%contractuel%'])->orWhereRaw('LOWER(libelle) LIKE ?', ['%vacataire%'])->pluck('id');
            $ids = Enseignant::where('est_actif', true)->whereNull('deleted_at')
                ->where(function ($q) use ($statuses) { $q->whereIn('statut_enseignant_id', $statuses)->orWhereIn('type_engagement', ['contractuel', 'vacataire']); })
                ->orderBy('id')->pluck('id')->all();
            if ($ids === []) throw ValidationException::withMessages(['code' => 'Aucun enseignant PC/VAC actif disponible pour le calcul national.']);
            app(PayrollTabaskiSchedule::class)->materialize($period, $ids, $actor);
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
            $actions = app(PayrollActionService::class);
            return $actions->closePeriod(['payroll_period_id' => $period->id, 'expected_version' => $period->version, 'confirmation' => $period->code], $actor, $request, null);
        }, 3);
    }
}
