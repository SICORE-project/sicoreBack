<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PayrollSalaryIncreases
{
    public function resolve(): Collection
    {
        $codes = collect(config('payroll_reference.contract_salary_increases', []))->pluck('code');
        $map = config('payroll_bulletin', []);
        $aliases = $codes->map(fn ($code) => $map[$code][0] ?? $code);
        $rubrics = DB::table('rubrique_paies')->whereIn('code', $codes->merge($aliases))->get()->keyBy('code');
        return $codes->map(function ($code) use ($rubrics, $map) {
            $matches = collect([$rubrics->get($code), $rubrics->get($map[$code][0] ?? $code)])->filter()->unique('id');
            $rubric = $matches->first();
            if (! $rubric || $matches->contains(fn ($item) => ! $item->est_actif || $item->type !== 'gain'
                || $item->periodicite !== 'mensuelle' || $item->montant === null || ! is_numeric($item->montant) || $item->montant < 0)
                || $matches->map(fn ($item) => (float) $item->montant)->unique()->count() > 1) {
                throw ValidationException::withMessages(['rubriques_paie' => $code.' : configurez un gain mensuel actif avec son montant dans Rubriques de paie, sans doublon contradictoire.']);
            }
            return ['code' => $code, 'label' => $rubric->libelle, 'amount' => round((float) $rubric->montant, 2), 'rubric_id' => $rubric->id];
        });
    }
}
