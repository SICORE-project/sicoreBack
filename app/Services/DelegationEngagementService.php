<?php

namespace App\Services;

use App\Models\DelegationCredit;
use App\Models\VentilationCredit;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DelegationEngagementService
{
    /**
     * Récupérer la période de paie de la délégation.
     */
    public function resolvePeriodId(
        DelegationCredit $delegation
    ): ?int {
        $periode = Str::upper(
            Str::ascii(trim((string) $delegation->periode_paie))
        );

        $mois = [
            'JANVIER' => '01',
            'FEVRIER' => '02',
            'MARS' => '03',
            'AVRIL' => '04',
            'MAI' => '05',
            'JUIN' => '06',
            'JUILLET' => '07',
            'AOUT' => '08',
            'SEPTEMBRE' => '09',
            'OCTOBRE' => '10',
            'NOVEMBRE' => '11',
            'DECEMBRE' => '12',
        ];

        if (! preg_match('/^([A-Z]+)\s+(\d{4})$/', $periode, $matches)) {
            return null;
        }

        if (! isset($mois[$matches[1]])) {
            return null;
        }

        $code = $matches[2].'-'.$mois[$matches[1]];

        $id = DB::table('payroll_periods')
            ->where('code', $code)
            ->value('id');

        return $id !== null ? (int) $id : null;
    }

    /**
     * Calculer l'engagement d'une ventilation.
     */
    public function calculate(
        DelegationCredit $delegation,
        VentilationCredit $ventilation
    ): ?float {
        // Les primes scolaires seront traitées séparément.
        if ($ventilation->type_ventilation !== 'salaire') {
            return null;
        }

        $periodId = $this->resolvePeriodId($delegation);

        if ($periodId === null) {
            return null;
        }

        $corps = DB::table('corps_enseignant')
            ->where('id', $ventilation->corps_enseignant_id)
            ->value('code');

        $typeEngagement = match ($corps) {
            'VAC' => 'vacataire',
            'PC' => 'contractuel',
            default => null,
        };

        if ($typeEngagement === null) {
            return null;
        }

        $query = DB::table('payroll_payslips as p')
            ->join(
                'enseignants as e',
                'e.id',
                '=',
                'p.enseignant_id'
            )
            ->where('p.payroll_period_id', $periodId)
            ->where('e.ia_id', $ventilation->ia_id)
            ->where('e.corps_id', $ventilation->corps_enseignant_id)
            ->where('e.type_engagement', $typeEngagement);

        if ($ventilation->ief_id !== null) {
            $query->where('e.ief_id', $ventilation->ief_id);
        }

        return (float) $query->sum('p.net_amount');
    }
}