<?php

namespace App\Services;

use App\Models\PayrollPeriod;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Seul endroit qui relie un mois du référentiel à une année académique.
 *
 * Règle d'appartenance : un mois appartient à l'année s'il chevauche
 * l'intervalle [date_debut, date_fin] de cette année. Elle ne sert qu'à la
 * création d'une période ; ensuite, le lien est la clé étrangère
 * payroll_periods.annee_academique_id.
 */
class PayrollMonthResolver
{
    /** Période déjà ouverte pour ce mois du référentiel dans cette année, sinon null. */
    public function resolve(int $month, ?int $academicYearId): ?PayrollPeriod
    {
        $this->assertCatalogueMonth($month);
        if (! $academicYearId) return null;
        if (! DB::table('annee_academiques')->where('id', $academicYearId)->exists()) {
            throw ValidationException::withMessages(['academic_year_id' => 'Année académique inconnue.']);
        }

        return PayrollPeriod::query()
            ->where('annee_academique_id', $academicYearId)
            ->whereMonth('start_date', $month)
            ->first();
    }

    /** Premier jour du mois $month dans l'année académique $year, selon la règle de chevauchement. */
    public function monthStart(int $month, object $year): CarbonImmutable
    {
        if (empty($year->date_debut) || empty($year->date_fin)) {
            throw ValidationException::withMessages(['annee_academique_id' => 'Année académique inconnue ou sans dates.']);
        }
        $dates = collect();
        $date = CarbonImmutable::parse($year->date_debut)->startOfMonth();
        $end = CarbonImmutable::parse($year->date_fin);
        while ($date->lessThanOrEqualTo($end)) {
            if ($date->month === $month) $dates->push($date);
            $date = $date->addMonth();
        }
        if ($dates->count() !== 1) {
            throw ValidationException::withMessages(['payroll_month' => $dates->isEmpty()
                ? 'Ce mois n’appartient pas à l’année académique choisie.'
                : 'Les dates de cette année académique couvrent plusieurs occurrences du même mois. Vérifiez ses dates.']);
        }

        return $dates->first();
    }

    /** Vrai si la période [start, end] chevauche les dates de l'année. */
    public function overlaps(object $year, CarbonImmutable $start, CarbonImmutable $end): bool
    {
        return CarbonImmutable::parse($year->date_debut)->lessThanOrEqualTo($end)
            && CarbonImmutable::parse($year->date_fin)->greaterThanOrEqualTo($start);
    }

    public function assertCatalogueMonth(int $month): void
    {
        if (! collect(app(PayrollPeriodCatalogue::class)->months())->contains('value', $month)) {
            throw ValidationException::withMessages(['payroll_month' => 'Ce mois n’est pas enregistré dans Paramétrage → Périodes de paie.']);
        }
    }
}
