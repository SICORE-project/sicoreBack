<?php

namespace App\Services;

use App\Models\PayrollPeriod;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/** Payroll-only bridge. The catalogue and academic years remain owned by Paramétrage. */
class PayrollPeriodCatalogue
{
    public function synchronize(): Collection
    {
        // Reading reports must never open or recreate a period.
        return PayrollPeriod::orderBy('code')->pluck('code');
    }

    public function months(): array
    {
        return DB::table('periode_de_paies')->orderBy('id')->get()
            ->map(fn ($reference) => ['value' => $this->month($reference), 'label' => $reference->libelle])
            ->filter(fn ($option) => $option['value'] !== null)->unique('value')->sortBy('value')->values()->all();
    }

    public function month(object $reference): ?int
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
