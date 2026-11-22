<?php

namespace App\Services;

use App\Models\Admin\User;
use App\Models\Enseignant;
use App\Models\PayrollPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** National monthly lifecycle, owned exclusively by the period creation workflow. */
class PayrollPeriodLifecycle
{
    /**
     * Opens the dated period of a catalogue month inside an academic year
     * (Paie → Édition). The reusable catalogue row is left untouched, so the
     * same month can be opened again for the next academic year.
     */
    public function openForMonth(int $month, int $academicYearId, User $actor, Request $request, ?string $key): PayrollPeriod
    {
        $catalogue = app(PayrollPeriodCatalogue::class);
        $reference = collect($catalogue->months())->firstWhere('value', $month);
        if (! $reference) {
            throw ValidationException::withMessages(['payroll_month' => 'Ce mois n’est pas enregistré dans Paramétrage → Périodes de paie.']);
        }

        return DB::transaction(function () use ($month, $academicYearId, $reference, $actor, $request, $key) {
            DB::table('annee_academiques')->orderBy('id')->lockForUpdate()->get();
            $year = DB::table('annee_academiques')->where('id', $academicYearId)->first();
            if (! $year) {
                throw ValidationException::withMessages(['annee_academique_id' => 'Année académique inconnue.']);
            }
            $date = app(PayrollMonthResolver::class)->monthStart($month, $year);
            $code = $date->format('Y-m');
            $existing = PayrollPeriod::where('code', $code)->lockForUpdate()->first();
            if ($existing) {
                // Never recalculate or reopen an existing payroll from this shortcut.
                if ($existing->annee_academique_id === null) {
                    $existing->forceFill(['annee_academique_id' => $academicYearId])->save();
                }
                return $existing;
            }
            $label = preg_match('/\b'.$date->year.'\b/', $reference['label']) ? $reference['label'] : $reference['label'].' '.$date->year;
            $period = PayrollPeriod::create(['code' => $code, 'label' => $label,
                'start_date' => $date->toDateString(), 'end_date' => $date->endOfMonth()->toDateString(), 'status' => 'open',
                'annee_academique_id' => $academicYearId]);

            return $this->calculateNational($period, $actor, $request, $key);
        }, 3);
    }

    private function calculateNational(PayrollPeriod $period, User $actor, Request $request, ?string $key): PayrollPeriod
    {
        $ids = Enseignant::query()->contractuelOuVacataire()->eligiblePourPeriode($period)
            ->orderBy('id')->pluck('id')->all();
        if ($ids === []) throw ValidationException::withMessages(['code' => 'Aucun enseignant PC/VAC actif et en service sur ce mois pour le calcul national. Vérifiez les dates de prise de service.']);
        app(PayrollTabaskiSchedule::class)->materialize($period, $ids, $actor);
        $run = app(PayrollCalculationService::class)->calculate($period, $actor, $ids);
        app(PayrollAuditService::class)->log('period.opened_and_calculated', $period, null, $run->toArray(), $actor, $request, $key);
        return $period->fresh();
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
