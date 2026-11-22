<?php

namespace App\Services;

use App\Models\PayrollPeriod;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/** Payroll-only bridge. The catalogue and academic years remain owned by Paramétrage. */
class PayrollPeriodCatalogue
{
    public function synchronize(): Collection
    {
        // Reading reports must never open or recreate a period.
        return PayrollPeriod::orderBy('code')->pluck('code');
    }

    /** Mois du référentiel : le numéro vient de la colonne numero_mois, jamais du code ou du libellé. */
    public function months(): array
    {
        return DB::table('periode_de_paies')->whereNotNull('numero_mois')->orderBy('numero_mois')->get()
            ->map(fn ($reference) => ['value' => (int) $reference->numero_mois, 'label' => $reference->libelle])
            ->unique('value')->values()->all();
    }
}
