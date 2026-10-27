<?php

namespace App\Services;

use App\Models\PayrollPeriod;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/** Payroll-only bridge. The catalogue and academic years remain owned by Paramétrage. */
class PayrollPeriodCatalogue
{
    public function synchronize(): Collection
    {
        $codes = collect();
        $years = DB::table('annee_academiques')->orderByDesc('date_debut')->get();
        foreach (DB::table('periode_de_paies')->orderBy('id')->get() as $reference) {
            $explicit = preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $reference->code) === 1;
            $month = $this->month($reference);
            if ($month === null) { continue; }
            foreach ($years as $year) {
                $date = CarbonImmutable::parse($year->date_debut)->startOfMonth();
                $end = CarbonImmutable::parse($year->date_fin)->endOfMonth();
                while ($date->lessThanOrEqualTo($end)) {
                    if ($date->month === $month && (! $explicit || $date->format('Y-m') === $reference->code)) {
                        $code = $date->format('Y-m');
                        $label = $reference->libelle;
                        if (! preg_match('/\b'.$date->year.'\b/', $label)) { $label .= ' '.$date->year; }
                        $period = PayrollPeriod::where('code', $code)->first();
                        if (! $period && ! $year->est_cloturee) {
                            $period = PayrollPeriod::firstOrCreate(['code' => $code], [
                                'label' => $label, 'start_date' => $date->toDateString(),
                                'end_date' => $date->endOfMonth()->toDateString(), 'status' => 'open',
                            ]);
                        }
                        if ($period) {
                            // Keep calculation/closure state and financial history untouched.
                            if ($period->status === 'open' && $period->label !== $label) {
                                $period->update(['label' => $label]);
                            }
                            $codes->push($code);
                        }
                    }
                    $date = $date->addMonth();
                }
            }
        }
        return $codes->unique()->values();
    }

    public function months(): array
    {
        return DB::table('periode_de_paies')->orderBy('id')->get()
            ->map(fn ($reference) => ['value' => $this->month($reference), 'label' => $reference->libelle])
            ->filter(fn ($option) => $option['value'] !== null)->unique('value')->sortBy('value')->values()->all();
    }

    private function month(object $reference): ?int
    {
        if (preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $reference->code)) {
            return (int) substr($reference->code, -2);
        }
        if (preg_match('/^(0?[1-9]|1[0-2])$/', $reference->code)) { return (int) $reference->code; }
        $names = ['JANVIER', 'FEVRIER', 'MARS', 'AVRIL', 'MAI', 'JUIN', 'JUILLET', 'AOUT', 'SEPTEMBRE', 'OCTOBRE', 'NOVEMBRE', 'DECEMBRE'];
        foreach ([$reference->code, $reference->libelle] as $value) {
            $normalized = Str::upper(Str::ascii(trim($value)));
            foreach ($names as $index => $name) {
                if ($normalized === $name || $normalized === substr($name, 0, 3)
                    || preg_match('/^'.$name.'\s+\d{4}$/', $normalized)) { return $index + 1; }
            }
        }
        return null;
    }
}
