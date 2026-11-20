<?php

namespace App\Services;

use App\Models\PayrollPeriod;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** Read-only bridge between the reusable month catalogue and dated treatments. */
class PayrollMonthResolver
{
    public function resolve(int $month, ?int $academicYearId): ?PayrollPeriod
    {
        if (! collect(app(PayrollPeriodCatalogue::class)->months())->contains('value', $month)) {
            throw ValidationException::withMessages(['payroll_month' => 'Ce mois n’est pas enregistré dans Paramétrage → Périodes de paie.']);
        }
        if (! $academicYearId) return null;
        $year = DB::table('annee_academiques')->where('id', $academicYearId)->first();
        if (! $year) throw ValidationException::withMessages(['academic_year_id' => 'Année académique inconnue.']);
        $dates = collect();
        $date = CarbonImmutable::parse($year->date_debut)->startOfMonth();
        $end = CarbonImmutable::parse($year->date_fin);
        while ($date->lessThanOrEqualTo($end)) {
            if ($date->month === $month) $dates->push($date->format('Y-m'));
            $date = $date->addMonth();
        }
        if ($dates->count() > 1) {
            throw ValidationException::withMessages(['academic_year_id' => 'Les dates de cette année académique couvrent plusieurs occurrences du même mois. Vérifiez ses dates.']);
        }
        if ($dates->isEmpty()) return null;
        return PayrollPeriod::where('code', $dates->first())->first();
    }
}
