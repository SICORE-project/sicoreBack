<?php

namespace App\Services;

use App\Models\Enseignant;
use App\Models\PayrollAllowanceRate;
use App\Models\PayrollParameter;
use App\Models\PayrollSalaryScale;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class PayrollReferenceService
{
    public const CONTRACTUEL = 'contractuel';

    public const VACATAIRE = 'vacataire';

    public const DIPLOMAS = [
        'CAP',
        'BEP',
        'BAC_BT',
        'BTS_DUEL_DUES',
        'LICENCE',
        'MASTER_MAITRISE',
    ];

    public const RESERVED_ELEMENT_CODES = [
        'SALAIRE_BASE',
        'PRIME_SPECIALE',
        'INDEMNITE_COMPENSATION',
        'IRD',
        'AUG_JAN_2002',
        'AUG_JAN_2003',
        'AUG_JAN_2004',
        'AUG_JAN_2005',
        'AUG_OCT_2005',
        'AUG_JAN_2006',
        'AUG_OCT_2018',
        'AUG_JAN_2019',
        'IPRES_SALARIE',
        'IPRES_EMPLOYEUR',
        'IPM',
        'IMPR',
        'TRIMF',
        'CHECKOFF_UES',
    ];

    /** @return array<string, float|int|string|null> */
    public function profile(Enseignant $teacher, CarbonInterface|string $date): array
    {
        $engagement = mb_strtolower(trim((string) $teacher->type_engagement));
        $matricule = $teacher->matricule ?: '#'.$teacher->id;

        if (! in_array($engagement, [self::CONTRACTUEL, self::VACATAIRE], true)) {
            throw new ConflictHttpException(
                "Le type d’engagement du formateur {$matricule} n’est pas configuré."
            );
        }
        if (! $teacher->payroll_profile_configured_at) {
            throw new ConflictHttpException(
                "Le profil de paie du formateur {$matricule} doit être configuré avant le calcul."
            );
        }
        if ($teacher->impr_monthly_amount === null || $teacher->trimf_monthly_amount === null) {
            throw new ConflictHttpException(
                "Les montants IMPR et TRIMF du formateur {$matricule} doivent être validés."
            );
        }

        $diploma = $engagement === self::CONTRACTUEL
            ? (string) $teacher->payroll_diploma_level
            : 'TOUS';
        $category = $engagement === self::CONTRACTUEL
            ? (int) $teacher->payroll_category_level
            : 0;

        if ($engagement === self::CONTRACTUEL && (
            ! in_array($diploma, self::DIPLOMAS, true)
            || $category < 1
            || $category > 12
        )) {
            throw new ConflictHttpException(
                "Le diplôme ou la catégorie de paie du formateur {$matricule} est incomplet."
            );
        }

        $scale = $this->dated(PayrollSalaryScale::query(), $date)
            ->where('engagement_type', $engagement)
            ->where('diploma_level', $diploma)
            ->where('category_level', $category)
            ->first();

        if (! $scale) {
            throw new ConflictHttpException(
                "Aucune grille salariale applicable au formateur {$matricule} pour cette période."
            );
        }

        $profile = [
            'engagement_type' => $engagement,
            'diploma_level' => $engagement === self::CONTRACTUEL ? $diploma : null,
            'diploma_label' => $engagement === self::CONTRACTUEL
                ? (config('payroll_reference.diplomas.'.$diploma) ?? $diploma)
                : null,
            'category_level' => $engagement === self::CONTRACTUEL ? $category : null,
            'base_salary' => (float) $scale->base_salary,
            'salary_scale_id' => $scale->id,
            'salary_scale_effective_from' => $scale->effective_from->toDateString(),
            'impr' => (float) $teacher->impr_monthly_amount,
            'trimf' => (float) $teacher->trimf_monthly_amount,
            'ipm' => (float) $teacher->ipm_monthly_amount,
            'checkoff' => (float) $teacher->union_checkoff_monthly_amount,
        ];

        if ($engagement === self::VACATAIRE) {
            return $profile;
        }

        $allowance = $this->dated(PayrollAllowanceRate::query(), $date)
            ->where('code', 'IRD')
            ->where('diploma_level', $diploma)
            ->first();
        if (! $allowance) {
            throw new ConflictHttpException(
                "Aucun barème IRD applicable au formateur {$matricule} pour cette période."
            );
        }

        return [
            ...$profile,
            'ird' => (float) $allowance->amount,
            'ird_rate_id' => $allowance->id,
            'prime_speciale' => $this->parameter(self::CONTRACTUEL, 'PRIME_SPECIALE', $date),
            'indemnite_compensation' => $this->parameter(self::CONTRACTUEL, 'INDEMNITE_COMPENSATION', $date),
            'ipres_employee_rate' => $this->parameter(self::CONTRACTUEL, 'IPRES_EMPLOYEE_RATE', $date),
            'ipres_employer_rate' => $this->parameter(self::CONTRACTUEL, 'IPRES_EMPLOYER_RATE', $date),
            'ipres_ceiling' => $this->parameter(self::CONTRACTUEL, 'IPRES_CEILING', $date),
        ];
    }

    public function salaryFor(
        string $engagement,
        ?string $diploma,
        ?int $category,
        CarbonInterface|string $date
    ): float {
        $scale = $this->dated(PayrollSalaryScale::query(), $date)
            ->where('engagement_type', $engagement)
            ->where('diploma_level', $engagement === self::VACATAIRE ? 'TOUS' : $diploma)
            ->where('category_level', $engagement === self::VACATAIRE ? 0 : $category)
            ->first();

        if (! $scale) {
            throw new ConflictHttpException('Aucune grille salariale ne correspond au profil sélectionné.');
        }

        return (float) $scale->base_salary;
    }

    private function parameter(string $engagement, string $code, CarbonInterface|string $date): float
    {
        $parameter = $this->dated(PayrollParameter::query(), $date)
            ->where('engagement_type', $engagement)
            ->where('code', $code)
            ->first();

        if (! $parameter) {
            throw new ConflictHttpException("Le paramètre de paie {$code} est absent pour cette période.");
        }

        return (float) $parameter->value;
    }

    private function dated(Builder $query, CarbonInterface|string $date): Builder
    {
        $value = $date instanceof CarbonInterface ? $date->toDateString() : $date;

        return $query
            ->where('is_active', true)
            ->whereDate('effective_from', '<=', $value)
            ->where(function (Builder $builder) use ($value): void {
                $builder->whereNull('effective_to')->orWhereDate('effective_to', '>=', $value);
            })
            ->orderByDesc('effective_from');
    }
}
