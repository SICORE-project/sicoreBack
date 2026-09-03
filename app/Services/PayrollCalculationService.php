<?php

namespace App\Services;

use App\Models\Enseignant;
use App\Models\Admin\User;
use App\Models\PayrollPeriod;
use App\Models\PayrollRun;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class PayrollCalculationService
{
    public function __construct(private readonly PayrollReferenceService $references) {}

    public function calculate(PayrollPeriod $period, User $actor): PayrollRun
    {
        return DB::transaction(function () use ($period, $actor): PayrollRun {
            $lockedPeriod = PayrollPeriod::query()->lockForUpdate()->findOrFail($period->id);

            if (! in_array($lockedPeriod->status, [
                PayrollPeriod::STATUS_OPEN,
                PayrollPeriod::STATUS_CALCULATED,
            ], true)) {
                throw new ConflictHttpException('Cette période ne peut plus être recalculée.');
            }

            if ($lockedPeriod->attendances()->where('status', 'draft')->exists()) {
                throw new ConflictHttpException(
                    'Tous les états de présence doivent être validés avant le calcul.'
                );
            }

            if ($lockedPeriod->elements()->where('status', 'draft')->exists()) {
                throw new ConflictHttpException(
                    'Tous les éléments variables doivent être validés avant le calcul.'
                );
            }

            $incompleteStructuredProfiles = Enseignant::query()
                ->where('actif', true)
                ->whereIn('type_engagement', [
                    PayrollReferenceService::CONTRACTUEL,
                    PayrollReferenceService::VACATAIRE,
                ])
                ->where(function ($query): void {
                    $query
                        ->whereNull('payroll_profile_configured_at')
                        ->orWhereNull('impr_monthly_amount')
                        ->orWhereNull('trimf_monthly_amount')
                        ->orWhere('salaire_base', '<=', 0)
                        ->orWhere(function ($contractQuery): void {
                            $contractQuery
                                ->where('type_engagement', PayrollReferenceService::CONTRACTUEL)
                                ->where(function ($requiredQuery): void {
                                    $requiredQuery
                                        ->whereNull('payroll_diploma_level')
                                        ->orWhereNull('payroll_category_level');
                                });
                        });
                })
                ->count();

            if ($incompleteStructuredProfiles > 0) {
                throw new ConflictHttpException(sprintf(
                    '%d profil(s) contractuel(s) ou vacataire(s) sont incomplets. Corrigez-les dans « Paie non générée » avant le calcul.',
                    $incompleteStructuredProfiles
                ));
            }

            $enseignants = Enseignant::query()
                ->with(['user', 'institutionFinanciere', 'corps', 'etablissement'])
                ->where('actif', true)
                ->where('salaire_base', '>', 0)
                ->orderBy('id')
                ->get();

            if ($enseignants->isEmpty()) {
                throw new ConflictHttpException(
                    'Aucun enseignant actif avec un salaire de base n’est disponible.'
                );
            }

            $run = PayrollRun::query()->updateOrCreate(
                ['payroll_period_id' => $lockedPeriod->id],
                [
                    'reference' => 'PAY-'.$lockedPeriod->code,
                    'status' => 'calculated',
                    'calculated_by' => $actor->id,
                    'calculated_at' => now(),
                    'validated_by' => null,
                    'validated_at' => null,
                    'employee_count' => 0,
                    'total_gross' => 0,
                    'total_deductions' => 0,
                    'total_employer_contributions' => 0,
                    'total_net' => 0,
                    'checksum' => str_repeat('0', 64),
                ]
            );

            $run->payslips()->delete();

            $totals = [
                'gross' => 0.0,
                'deductions' => 0.0,
                'employer' => 0.0,
                'net' => 0.0,
            ];
            $checksums = [];

            foreach ($enseignants as $enseignant) {
                $snapshot = $this->calculateTeacher($lockedPeriod, $enseignant);
                $payslip = $run->payslips()->create([
                    'payroll_period_id' => $lockedPeriod->id,
                    'enseignant_id' => $enseignant->id,
                    'reference' => sprintf(
                        'BS-%s-%s',
                        str_replace('-', '', $lockedPeriod->code),
                        $enseignant->matricule ?: str_pad((string) $enseignant->id, 6, '0', STR_PAD_LEFT)
                    ),
                    'profile_snapshot' => $snapshot['profile'],
                    'gross_amount' => $snapshot['gross'],
                    'deduction_amount' => $snapshot['deductions'],
                    'employer_contribution_amount' => $snapshot['employer'],
                    'net_amount' => $snapshot['net'],
                    'payment_status' => 'pending',
                ]);

                $payslip->lines()->createMany($snapshot['lines']);

                $totals['gross'] += $snapshot['gross'];
                $totals['deductions'] += $snapshot['deductions'];
                $totals['employer'] += $snapshot['employer'];
                $totals['net'] += $snapshot['net'];
                $checksums[] = $payslip->reference.':'.$snapshot['checksum'];
            }

            $checksum = hash('sha256', implode('|', $checksums));
            $run->update([
                'employee_count' => $enseignants->count(),
                'total_gross' => round($totals['gross'], 2),
                'total_deductions' => round($totals['deductions'], 2),
                'total_employer_contributions' => round($totals['employer'], 2),
                'total_net' => round($totals['net'], 2),
                'checksum' => $checksum,
            ]);

            $lockedPeriod->update([
                'status' => PayrollPeriod::STATUS_CALCULATED,
                'employee_count' => $enseignants->count(),
                'total_gross' => round($totals['gross'], 2),
                'total_deductions' => round($totals['deductions'], 2),
                'total_net' => round($totals['net'], 2),
                'checksum' => $checksum,
                'calculated_at' => now(),
                'calculated_by' => $actor->id,
                'validated_at' => null,
                'validated_by' => null,
                'version' => $lockedPeriod->version + 1,
            ]);

            return $run->fresh(['period', 'payslips.lines']);
        }, 3);
    }

    /** @return array{gross: float, deductions: float, employer: float, net: float, checksum: string, profile: array<string, mixed>|null, lines: array<int, array<string, mixed>>} */
    private function calculateTeacher(PayrollPeriod $period, Enseignant $enseignant): array
    {
        if (in_array($enseignant->type_engagement, [
            PayrollReferenceService::CONTRACTUEL,
            PayrollReferenceService::VACATAIRE,
        ], true)) {
            return $this->calculateStructuredTeacher($period, $enseignant);
        }

        return $this->calculateLegacyTeacher($period, $enseignant);
    }

    /** @return array{gross: float, deductions: float, employer: float, net: float, checksum: string, profile: array<string, mixed>, lines: array<int, array<string, mixed>>} */
    private function calculateStructuredTeacher(PayrollPeriod $period, Enseignant $enseignant): array
    {
        $profile = $this->references->profile($enseignant, $period->end_date);
        $base = round((float) $profile['base_salary'], 2);
        $elements = $period->elements()
            ->where('enseignant_id', $enseignant->id)
            ->where('status', 'validated')
            ->where('is_exempt', false)
            ->orderBy('id')
            ->get();
        $attendance = $period->attendances()
            ->where('enseignant_id', $enseignant->id)
            ->where('status', 'validated')
            ->first();

        $reserved = $elements->pluck('code')
            ->intersect(PayrollReferenceService::RESERVED_ELEMENT_CODES)
            ->values();
        if ($reserved->isNotEmpty()) {
            throw new ConflictHttpException(sprintf(
                'Le formateur %s possède des éléments réservés au calcul automatique : %s.',
                $enseignant->matricule ?: '#'.$enseignant->id,
                $reserved->implode(', ')
            ));
        }

        $lines = [];
        $fixedEarnings = $base;

        if ($profile['engagement_type'] === PayrollReferenceService::CONTRACTUEL) {
            $salaryIncreases = collect(config('payroll_reference.contract_salary_increases', []));
            $increaseTotal = round((float) $salaryIncreases->sum('amount'), 2);
            $salaryOrigin = round($base - $increaseTotal, 2);
            if ($salaryOrigin <= 0) {
                throw new ConflictHttpException(
                    'La décomposition historique des augmentations dépasse le salaire contractuel courant.'
                );
            }

            $lines[] = [
                'code' => 'SALAIRE_BASE',
                'label' => 'Salaire de base avant augmentations',
                'category' => 'earning',
                'amount' => $salaryOrigin,
                'source' => 'salary_scale',
                'sort_order' => 10,
            ];
            foreach ($salaryIncreases->values() as $index => $increase) {
                $lines[] = [
                    'code' => $increase['code'],
                    'label' => $increase['label'],
                    'category' => 'earning',
                    'amount' => round((float) $increase['amount'], 2),
                    'source' => 'salary_increase',
                    'sort_order' => 11 + $index,
                ];
            }
            $profile['salary_origin'] = $salaryOrigin;
            $profile['salary_increases'] = $salaryIncreases->values()->all();

            $contractEarnings = [
                ['PRIME_SPECIALE', 'Prime spéciale', (float) $profile['prime_speciale'], 20],
                ['INDEMNITE_COMPENSATION', 'Indemnité de compensation', (float) $profile['indemnite_compensation'], 30],
                ['IRD', 'Indice de Recherche et de Documentation (IRD)', (float) $profile['ird'], 40],
            ];
            foreach ($contractEarnings as [$code, $label, $amount, $sort]) {
                if ($amount <= 0) {
                    continue;
                }
                $fixedEarnings += $amount;
                $lines[] = [
                    'code' => $code,
                    'label' => $label,
                    'category' => 'earning',
                    'amount' => $amount,
                    'source' => 'payroll_reference',
                    'sort_order' => $sort,
                ];
            }
        } else {
            $lines[] = [
                'code' => 'SALAIRE_BASE',
                'label' => 'Salaire de base',
                'category' => 'earning',
                'amount' => $base,
                'source' => 'salary_scale',
                'sort_order' => 10,
            ];
        }

        foreach ($elements as $index => $element) {
            $lines[] = [
                'code' => $element->code,
                'label' => $element->label,
                'category' => $element->category,
                'amount' => (float) $element->amount,
                'source' => $element->source,
                'sort_order' => 50 + $index,
            ];
        }

        $earnings = round($fixedEarnings + $this->sumElements($elements, 'earning'), 2);
        $manualDeductions = $this->sumElements($elements, 'deduction')
            + $this->sumElements($elements, 'contribution');
        $absenceDeduction = $attendance ? (float) $attendance->deduction_amount : 0.0;

        if ($absenceDeduction > 0) {
            $lines[] = [
                'code' => 'ABSENCE',
                'label' => 'Retenue pour absence',
                'category' => 'deduction',
                'amount' => $absenceDeduction,
                'source' => 'attendance',
                'sort_order' => 70,
            ];
        }

        $systemDeductions = 0.0;
        $employerContribution = 0.0;
        if ($profile['engagement_type'] === PayrollReferenceService::CONTRACTUEL) {
            $contributionBase = min($earnings, (float) $profile['ipres_ceiling']);
            $employeeIpres = round($contributionBase * (float) $profile['ipres_employee_rate'], 2);
            $employerContribution = round($contributionBase * (float) $profile['ipres_employer_rate'], 2);
            $contractDeductions = [
                ['IPRES_SALARIE', 'IPRES — part salariale', 'contribution', $employeeIpres, 80],
                ['IPM', 'IPM', 'contribution', (float) $profile['ipm'], 85],
                ['IMPR', 'Impôt mensuel sur le revenu (IMPR)', 'deduction', (float) $profile['impr'], 90],
                ['TRIMF', 'Taxe représentative de l’impôt du minimum fiscal (TRIMF)', 'deduction', (float) $profile['trimf'], 95],
                ['CHECKOFF_UES', 'Check-off UES', 'deduction', (float) $profile['checkoff'], 96],
            ];
            foreach ($contractDeductions as [$code, $label, $category, $amount, $sort]) {
                if ($amount <= 0) {
                    continue;
                }
                $systemDeductions += $amount;
                $lines[] = [
                    'code' => $code,
                    'label' => $label,
                    'category' => $category,
                    'amount' => $amount,
                    'source' => 'payroll_profile',
                    'sort_order' => $sort,
                ];
            }
            if ($employerContribution > 0) {
                $lines[] = [
                    'code' => 'IPRES_EMPLOYEUR',
                    'label' => 'IPRES — part employeur',
                    'category' => 'employer_contribution',
                    'amount' => $employerContribution,
                    'source' => 'payroll_reference',
                    'sort_order' => 100,
                ];
            }
        } else {
            $vacataireDeductions = [
                ['IMPR', 'Impôt mensuel sur le revenu (IMPR)', (float) $profile['impr'], 90],
                ['TRIMF', 'Taxe représentative de l’impôt du minimum fiscal (TRIMF)', (float) $profile['trimf'], 95],
            ];
            foreach ($vacataireDeductions as [$code, $label, $amount, $sort]) {
                if ($amount <= 0) {
                    continue;
                }
                $systemDeductions += $amount;
                $lines[] = [
                    'code' => $code,
                    'label' => $label,
                    'category' => 'deduction',
                    'amount' => $amount,
                    'source' => 'payroll_profile',
                    'sort_order' => $sort,
                ];
            }
        }

        $deductions = round($manualDeductions + $absenceDeduction + $systemDeductions, 2);
        $net = round($earnings - $deductions, 2);
        $this->assertPositiveNet($enseignant, $net);

        $profileSnapshot = [
            ...$profile,
            'calculation_model' => 'sicore-pc-vacataire-v1',
            'configured_at' => $enseignant->payroll_profile_configured_at?->toIso8601String(),
        ];
        $checksumPayload = ['profile' => $profileSnapshot, 'lines' => $lines];

        return [
            'gross' => $earnings,
            'deductions' => $deductions,
            'employer' => $employerContribution,
            'net' => $net,
            'profile' => $profileSnapshot,
            'lines' => $lines,
            'checksum' => hash('sha256', json_encode($checksumPayload, JSON_THROW_ON_ERROR)),
        ];
    }

    /** @return array{gross: float, deductions: float, employer: float, net: float, checksum: string, profile: null, lines: array<int, array<string, mixed>>} */
    private function calculateLegacyTeacher(PayrollPeriod $period, Enseignant $enseignant): array
    {
        $base = round((float) $enseignant->salaire_base, 2);
        $elements = $period->elements()
            ->where('enseignant_id', $enseignant->id)
            ->where('status', 'validated')
            ->where('is_exempt', false)
            ->orderBy('id')
            ->get();
        $attendance = $period->attendances()
            ->where('enseignant_id', $enseignant->id)
            ->where('status', 'validated')
            ->first();

        $lines = [[
            'code' => 'SALAIRE_BASE',
            'label' => 'Salaire de base',
            'category' => 'earning',
            'amount' => $base,
            'source' => 'system',
            'sort_order' => 10,
        ]];

        $earnings = $base + $this->sumElements($elements, 'earning');
        $manualDeductions = $this->sumElements($elements, 'deduction')
            + $this->sumElements($elements, 'contribution');
        $absenceDeduction = $attendance ? (float) $attendance->deduction_amount : 0.0;
        $social = round($earnings * (float) config('payroll.employee_social_rate'), 2);
        $taxable = max(0, $earnings - (float) config('payroll.income_tax_allowance'));
        $shares = max(1, (int) $enseignant->nombre_parts);
        $incomeTax = round(($taxable * (float) config('payroll.income_tax_rate')) / $shares, 2);
        $employerContribution = round(
            $earnings * (float) config('payroll.employer_social_rate'),
            2
        );

        foreach ($elements as $index => $element) {
            $lines[] = [
                'code' => $element->code,
                'label' => $element->label,
                'category' => $element->category,
                'amount' => (float) $element->amount,
                'source' => $element->source,
                'sort_order' => 20 + $index,
            ];
        }

        if ($absenceDeduction > 0) {
            $lines[] = [
                'code' => 'ABSENCE',
                'label' => 'Retenue pour absence',
                'category' => 'deduction',
                'amount' => $absenceDeduction,
                'source' => 'attendance',
                'sort_order' => 70,
            ];
        }

        $lines[] = [
            'code' => 'COTISATION_SOCIALE',
            'label' => 'Cotisation sociale salariale',
            'category' => 'contribution',
            'amount' => $social,
            'source' => 'system',
            'sort_order' => 80,
        ];
        $lines[] = [
            'code' => 'IMPOT_REVENU',
            'label' => 'Retenue fiscale',
            'category' => 'deduction',
            'amount' => $incomeTax,
            'source' => 'system',
            'sort_order' => 90,
        ];
        $lines[] = [
            'code' => 'CHARGE_EMPLOYEUR',
            'label' => 'Contribution sociale employeur',
            'category' => 'employer_contribution',
            'amount' => $employerContribution,
            'source' => 'system',
            'sort_order' => 100,
        ];

        $deductions = round(
            $manualDeductions + $absenceDeduction + $social + $incomeTax,
            2
        );
        $net = round($earnings - $deductions, 2);

        $this->assertPositiveNet($enseignant, $net);

        return [
            'gross' => round($earnings, 2),
            'deductions' => $deductions,
            'employer' => $employerContribution,
            'net' => $net,
            'profile' => null,
            'lines' => $lines,
            'checksum' => hash('sha256', json_encode($lines, JSON_THROW_ON_ERROR)),
        ];
    }

    private function sumElements(Collection $elements, string $category): float
    {
        return round(
            $elements
                ->where('category', $category)
                ->sum(fn ($element): float => (float) $element->amount),
            2
        );
    }

    private function assertPositiveNet(Enseignant $enseignant, float $net): void
    {
        if ($net < 0) {
            throw new ConflictHttpException(sprintf(
                'Le net à payer de %s est négatif. Corrigez ses retenues.',
                $enseignant->matricule ?: 'l’enseignant #'.$enseignant->id
            ));
        }
    }
}
