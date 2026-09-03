<?php

namespace Database\Seeders;

use App\Models\PayrollAttendance;
use App\Models\PayrollElement;
use App\Models\PayrollPayslip;
use App\Models\PayrollPeriod;
use App\Models\PayrollRun;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class GestionPaieTestSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $corps = DB::table('corps_enseignant')
                ->whereIn('code', ['VAC', 'PC'])
                ->pluck('id', 'code');

            if (! $corps->has('VAC') || ! $corps->has('PC')) {
                throw new RuntimeException(
                    'Exécutez GestionPaieSeeder avant GestionPaieTestSeeder pour créer les corps VAC et PC.'
                );
            }

            $hierarchy = [
                [
                    'ia' => ['code' => 'IA-DKR', 'libelle' => 'IA Dakar'],
                    'iefs' => [
                        ['code' => 'IEF-DKR-PLT', 'libelle' => 'IEF Dakar Plateau'],
                        ['code' => 'IEF-DKR-ALM', 'libelle' => 'IEF Dakar Almadies'],
                    ],
                ],
                [
                    'ia' => ['code' => 'IA-THS', 'libelle' => 'IA Thiès'],
                    'iefs' => [
                        ['code' => 'IEF-THS-VIL', 'libelle' => 'IEF Thiès Ville'],
                        ['code' => 'IEF-THS-MBR', 'libelle' => 'IEF Mbour'],
                    ],
                ],
                [
                    'ia' => ['code' => 'IA-STL', 'libelle' => 'IA Saint-Louis'],
                    'iefs' => [
                        ['code' => 'IEF-STL-COM', 'libelle' => 'IEF Saint-Louis Commune'],
                        ['code' => 'IEF-STL-DAG', 'libelle' => 'IEF Dagana'],
                    ],
                ],
                [
                    'ia' => ['code' => 'IA-KLK', 'libelle' => 'IA Kaolack'],
                    'iefs' => [
                        ['code' => 'IEF-KLK-COM', 'libelle' => 'IEF Kaolack Commune'],
                        ['code' => 'IEF-KLK-NIO', 'libelle' => 'IEF Nioro'],
                    ],
                ],
                [
                    'ia' => ['code' => 'IA-ZIG', 'libelle' => 'IA Ziguinchor'],
                    'iefs' => [
                        ['code' => 'IEF-ZIG-COM', 'libelle' => 'IEF Ziguinchor'],
                        ['code' => 'IEF-ZIG-BIG', 'libelle' => 'IEF Bignona'],
                    ],
                ],
                [
                    'ia' => ['code' => 'IA-DBL', 'libelle' => 'IA Diourbel'],
                    'iefs' => [
                        ['code' => 'IEF-DBL-COM', 'libelle' => 'IEF Diourbel'],
                        ['code' => 'IEF-DBL-MBK', 'libelle' => 'IEF Mbacké'],
                    ],
                ],
            ];

            $inspections = [];
            foreach ($hierarchy as $item) {
                $iaId = $this->referenceId('ias', $item['ia']);

                foreach ($item['iefs'] as $ief) {
                    $iefId = $this->referenceId('iefs', [
                        ...$ief,
                        'ia_id' => $iaId,
                    ]);
                    $inspections[] = [
                        'ia_id' => $iaId,
                        'ief_id' => $iefId,
                    ];
                }
            }

            $teachers = [
                ['matricule' => 'VAC-TEST-001', 'prenom' => 'Awa', 'nom' => 'Ndiaye', 'corps' => 'VAC', 'salary' => 150000, 'engagement' => 'vacataire'],
                ['matricule' => 'PC-TEST-001', 'prenom' => 'Moussa', 'nom' => 'Diop', 'corps' => 'PC', 'salary' => 152773, 'engagement' => 'contractuel', 'category' => 1],
                ['matricule' => 'VAC-TEST-002', 'prenom' => 'Fatou', 'nom' => 'Sarr', 'corps' => 'VAC', 'salary' => 150000, 'engagement' => 'vacataire'],
                ['matricule' => 'PC-TEST-002', 'prenom' => 'Ibrahima', 'nom' => 'Fall', 'corps' => 'PC', 'salary' => 157662, 'engagement' => 'contractuel', 'category' => 2],
                ['matricule' => 'VAC-TEST-003', 'prenom' => 'Mariama', 'nom' => 'Ba', 'corps' => 'VAC', 'salary' => 150000, 'engagement' => 'vacataire'],
                ['matricule' => 'PC-TEST-003', 'prenom' => 'Cheikh', 'nom' => 'Diallo', 'corps' => 'PC', 'salary' => 162795, 'engagement' => 'contractuel', 'category' => 3],
                ['matricule' => 'VAC-TEST-004', 'prenom' => 'Aminata', 'nom' => 'Cissé', 'corps' => 'VAC', 'salary' => 150000, 'engagement' => 'vacataire', 'configured' => true],
                ['matricule' => 'PC-TEST-004', 'prenom' => 'Abdoulaye', 'nom' => 'Sow', 'corps' => 'PC', 'salary' => 167540, 'engagement' => 'contractuel', 'category' => 4, 'configured' => true],
                ['matricule' => 'VAC-TEST-005', 'prenom' => 'Rokhaya', 'nom' => 'Faye', 'corps' => 'VAC', 'salary' => 150000, 'engagement' => 'vacataire', 'configured' => false],
                ['matricule' => 'PC-TEST-005', 'prenom' => 'Ousmane', 'nom' => 'Kane', 'corps' => 'PC', 'salary' => 172420, 'engagement' => 'contractuel', 'category' => 5, 'configured' => false],
                ['matricule' => 'VAC-TEST-006', 'prenom' => 'Khady', 'nom' => 'Mbaye', 'corps' => 'VAC', 'salary' => 150000, 'engagement' => 'vacataire', 'configured' => true],
                ['matricule' => 'PC-TEST-006', 'prenom' => 'Mamadou', 'nom' => 'Diallo', 'corps' => 'PC', 'salary' => 177310, 'engagement' => 'contractuel', 'category' => 6, 'configured' => true],
                ['matricule' => 'VAC-TEST-007', 'prenom' => 'Sokhna', 'nom' => 'Guèye', 'corps' => 'VAC', 'salary' => 150000, 'engagement' => 'vacataire', 'configured' => false],
                ['matricule' => 'PC-TEST-007', 'prenom' => 'Alioune', 'nom' => 'Seck', 'corps' => 'PC', 'salary' => 182205, 'engagement' => 'contractuel', 'category' => 7, 'configured' => false],
                ['matricule' => 'VAC-TEST-008', 'prenom' => 'Ndèye', 'nom' => 'Fall', 'corps' => 'VAC', 'salary' => 150000, 'engagement' => 'vacataire', 'configured' => true],
                ['matricule' => 'PC-TEST-008', 'prenom' => 'Babacar', 'nom' => 'Ndiaye', 'corps' => 'PC', 'salary' => 187095, 'engagement' => 'contractuel', 'category' => 8, 'configured' => true],
                ['matricule' => 'VAC-TEST-009', 'prenom' => 'Astou', 'nom' => 'Diop', 'corps' => 'VAC', 'salary' => 150000, 'engagement' => 'vacataire', 'configured' => false],
                ['matricule' => 'PC-TEST-009', 'prenom' => 'Pape', 'nom' => 'Sarr', 'corps' => 'PC', 'salary' => 191980, 'engagement' => 'contractuel', 'category' => 9, 'configured' => false],
            ];

            $period = PayrollPeriod::query()
                ->where('status', PayrollPeriod::STATUS_OPEN)
                ->latest('start_date')
                ->first();

            if (! $period) {
                throw new RuntimeException(
                    'Exécutez GestionPaieSeeder avant GestionPaieTestSeeder pour créer les périodes de paie.'
                );
            }

            $seededTeachers = [];
            foreach ($teachers as $index => $teacher) {
                $inspection = $inspections[$index % count($inspections)];
                $configured = $teacher['configured'] ?? true;
                $teacherId = $this->teacherId([
                    'matricule' => $teacher['matricule'],
                    'prenom' => $teacher['prenom'],
                    'nom' => $teacher['nom'],
                    'email' => mb_strtolower($teacher['matricule']).'@paie-test.sicore.local',
                    'corps_id' => $corps->get($teacher['corps']),
                    'ia_id' => $inspection['ia_id'],
                    'ief_id' => $inspection['ief_id'],
                    'salaire_brut' => $teacher['salary'],
                    'salaire_base' => $teacher['salary'],
                    'type_engagement' => $configured ? $teacher['engagement'] : null,
                    'payroll_diploma_level' => $configured && $teacher['engagement'] === 'contractuel' ? 'BAC_BT' : null,
                    'payroll_category_level' => $configured ? ($teacher['category'] ?? null) : null,
                    'impr_monthly_amount' => $configured ? ($teacher['engagement'] === 'contractuel' ? 11767 : 10500) : null,
                    'trimf_monthly_amount' => $configured ? ($teacher['engagement'] === 'contractuel' ? 500 : 400) : null,
                    'ipm_monthly_amount' => $configured && $teacher['engagement'] === 'contractuel' ? 4500 : 0,
                    'union_checkoff_monthly_amount' => $configured && $teacher['engagement'] === 'contractuel' ? 1000 : 0,
                    'payroll_profile_configured_at' => $configured ? now() : null,
                    'numero_compte' => 'TEST-'.str_pad((string) ($index + 1), 12, '0', STR_PAD_LEFT),
                    'statut' => 'en_activite',
                    'est_actif' => true,
                    'actif' => true,
                    'observations' => 'Donnée temporaire créée par GestionPaieTestSeeder.',
                ]);

                $absenceDays = ($index % 3) * 0.5;
                $delayMinutes = $index * 10;
                $deduction = round(
                    (($teacher['salary'] / 30) * $absenceDays)
                    + (($teacher['salary'] / 30 / 480) * $delayMinutes),
                    2
                );

                PayrollAttendance::query()->firstOrCreate(
                    [
                        'payroll_period_id' => $period->id,
                        'enseignant_id' => $teacherId,
                    ],
                    [
                        'absence_days' => $absenceDays,
                        'delay_minutes' => $delayMinutes,
                        'deduction_amount' => $deduction,
                        'status' => 'draft',
                        'notes' => 'État de présence de démonstration pour les tests de paie.',
                        'version' => 1,
                    ]
                );

                $seededTeachers[] = [
                    ...$teacher,
                    'id' => $teacherId,
                    'corps_id' => (int) $corps->get($teacher['corps']),
                    'ia_id' => $inspection['ia_id'],
                    'ief_id' => $inspection['ief_id'],
                ];
            }

            $this->seedPayrollElements($period, $seededTeachers);
            $this->seedPaidPayslips($period, array_slice($seededTeachers, 0, 6));
        });
    }

    /** @param array<int, array<string, mixed>> $teachers */
    private function seedPayrollElements(PayrollPeriod $period, array $teachers): void
    {
        $academicYear = DB::table('annee_academiques')
            ->whereDate('date_debut', '<=', $period->start_date->toDateString())
            ->whereDate('date_fin', '>=', $period->end_date->toDateString())
            ->first();

        foreach ($teachers as $index => $teacher) {
            $context = [
                'academic_year' => $academicYear?->libelle,
                'annee_academique_id' => $academicYear?->id,
                'application_scope' => 'collective',
                'application_corps_id' => $teacher['corps_id'],
                'application_ia_id' => $teacher['ia_id'],
                'application_ief_id' => $teacher['ief_id'],
                'applied_at' => now(),
                'source' => 'manual',
                'version' => 1,
            ];

            PayrollElement::query()->firstOrCreate(
                [
                    'payroll_period_id' => $period->id,
                    'enseignant_id' => $teacher['id'],
                    'code' => 'TABASKI_AVANCE',
                ],
                [
                    ...$context,
                    'label' => 'Avance Tabaski',
                    'category' => 'earning',
                    'amount' => 100000,
                    'application_reference' => 'TEST-AVANCE-'.$period->code,
                    'status' => $index < 3 ? 'validated' : 'draft',
                ]
            );

            PayrollElement::query()->firstOrCreate(
                [
                    'payroll_period_id' => $period->id,
                    'enseignant_id' => $teacher['id'],
                    'code' => 'TABASKI_RETENUE',
                ],
                [
                    ...$context,
                    'label' => 'Retenue Tabaski',
                    'category' => 'deduction',
                    'amount' => 10000,
                    'application_reference' => 'TEST-RETENUE-'.$period->code,
                    'is_exempt' => $index === 0,
                    'exemption_reason' => $index === 0
                        ? 'Exemption temporaire créée pour tester la page des exemptions.'
                        : null,
                    'status' => 'draft',
                ]
            );

            PayrollElement::query()->firstOrCreate(
                [
                    'payroll_period_id' => $period->id,
                    'enseignant_id' => $teacher['id'],
                    'code' => 'RAPPEL_RETENUE',
                ],
                [
                    ...$context,
                    'label' => 'Retenue sur rappel',
                    'category' => 'deduction',
                    'amount' => 5000 + ($index * 1000),
                    'application_scope' => 'individual',
                    'application_reference' => 'TEST-RAPPEL-'.$period->code.'-'.$teacher['id'],
                    'is_exempt' => $index === 1,
                    'exemption_reason' => $index === 1
                        ? 'Exemption temporaire sur rappel pour les tests de paie.'
                        : null,
                    'status' => $index % 2 === 0 ? 'validated' : 'draft',
                ]
            );

            PayrollElement::query()->firstOrCreate(
                [
                    'payroll_period_id' => $period->id,
                    'enseignant_id' => $teacher['id'],
                    'code' => 'COTISATION_TEST',
                ],
                [
                    ...$context,
                    'label' => 'Cotisation de démonstration',
                    'category' => 'contribution',
                    'amount' => 2500,
                    'application_scope' => 'individual',
                    'application_reference' => 'TEST-COTISATION-'.$period->code.'-'.$teacher['id'],
                    'status' => 'validated',
                ]
            );
        }
    }

    /** @param array<int, array<string, mixed>> $teachers */
    private function seedPaidPayslips(PayrollPeriod $period, array $teachers): void
    {
        $references = collect($teachers)->mapWithKeys(
            fn (array $teacher): array => [$teacher['id'] => $this->referencePayslip($teacher)]
        );
        $totalGross = $references->sum('gross');
        $totalDeductions = $references->sum('deductions');
        $totalEmployerContributions = $references->sum('employer');
        $totalNet = $references->sum('net');

        $run = PayrollRun::query()->firstOrCreate(
            ['payroll_period_id' => $period->id],
            [
                'reference' => 'PAY-TEST-'.$period->code,
                'status' => 'validated',
                'employee_count' => count($teachers),
                'total_gross' => $totalGross,
                'total_deductions' => $totalDeductions,
                'total_employer_contributions' => $totalEmployerContributions,
                'total_net' => $totalNet,
                'checksum' => hash('sha256', 'PAY-TEST-'.$period->code),
                'calculated_at' => now(),
                'validated_at' => now(),
            ]
        );

        if ($run->reference !== 'PAY-TEST-'.$period->code) {
            return;
        }

        $run->update([
            'status' => 'validated',
            'employee_count' => count($teachers),
            'total_gross' => $totalGross,
            'total_deductions' => $totalDeductions,
            'total_employer_contributions' => $totalEmployerContributions,
            'total_net' => $totalNet,
            'checksum' => hash('sha256', 'PAY-TEST-'.$period->code),
            'calculated_at' => now(),
            'validated_at' => now(),
        ]);

        foreach ($teachers as $index => $teacher) {
            $referenceData = $references->get($teacher['id']);
            $gross = $referenceData['gross'];
            $deductions = $referenceData['deductions'];
            $reference = 'BS-TEST-'.str_replace('-', '', $period->code).'-'.str_pad(
                (string) ($index + 1),
                3,
                '0',
                STR_PAD_LEFT
            );

            $payslip = PayrollPayslip::query()->firstOrCreate(
                [
                    'payroll_period_id' => $period->id,
                    'enseignant_id' => $teacher['id'],
                ],
                [
                    'payroll_run_id' => $run->id,
                    'reference' => $reference,
                    'profile_snapshot' => $referenceData['profile'],
                    'gross_amount' => $gross,
                    'deduction_amount' => $deductions,
                    'employer_contribution_amount' => $referenceData['employer'],
                    'net_amount' => $referenceData['net'],
                    'payment_status' => 'paid',
                    'payment_reference' => 'VIR-TEST-'.str_replace('-', '', $period->code).'-'.str_pad(
                        (string) ($index + 1),
                        3,
                        '0',
                        STR_PAD_LEFT
                    ),
                    'paid_at' => $period->end_date,
                    'version' => 1,
                ]
            );

            if ($payslip->reference !== $reference) {
                continue;
            }

            $payslip->update([
                'payroll_run_id' => $run->id,
                'profile_snapshot' => $referenceData['profile'],
                'gross_amount' => $gross,
                'deduction_amount' => $deductions,
                'employer_contribution_amount' => $referenceData['employer'],
                'net_amount' => $referenceData['net'],
                'payment_status' => 'paid',
            ]);
            $payslip->lines()->delete();
            $payslip->lines()->createMany($referenceData['lines']);
        }

        $period->update([
            'employee_count' => count($teachers),
            'total_gross' => $totalGross,
            'total_deductions' => $totalDeductions,
            'total_net' => $totalNet,
        ]);
    }

    /**
     * Reproduit les lignes visibles sur les deux bulletins reçus. Les
     * augmentations contractuelles sont détaillées pour la traçabilité, mais
     * ne sont pas additionnées deux fois puisqu'elles composent le salaire
     * contractuel courant.
     *
     * @param  array<string, mixed>  $teacher
     * @return array<string, mixed>
     */
    private function referencePayslip(array $teacher): array
    {
        if ($teacher['engagement'] === 'vacataire') {
            $lines = [
                $this->payslipLine('SALAIRE_BASE', 'Salaire de base', 'earning', 150000, 'salary_scale', 10),
                $this->payslipLine('IMPR', 'Impôt mensuel sur le revenu (IMPR)', 'deduction', 10500, 'payroll_profile', 90),
                $this->payslipLine('TRIMF', 'Taxe représentative de l’impôt du minimum fiscal (TRIMF)', 'deduction', 400, 'payroll_profile', 95),
                $this->payslipLine('TABASKI_RETENUE', 'Retenue Tabaski', 'deduction', 10000, 'manual', 97),
            ];

            return [
                'gross' => 150000,
                'deductions' => 20900,
                'employer' => 0,
                'net' => 129100,
                'profile' => [
                    'engagement_type' => 'vacataire',
                    'diploma_label' => null,
                    'category_level' => null,
                    'calculation_model' => 'sicore-pc-vacataire-v1',
                ],
                'lines' => $lines,
            ];
        }

        $salary = (float) $teacher['salary'];
        $increases = collect(config('payroll_reference.contract_salary_increases', []));
        $salaryOrigin = $salary - (float) $increases->sum('amount');
        $lines = [
            $this->payslipLine('SALAIRE_BASE', 'Salaire de base avant augmentations', 'earning', $salaryOrigin, 'salary_scale', 10),
        ];
        foreach ($increases->values() as $index => $increase) {
            $lines[] = $this->payslipLine(
                $increase['code'],
                $increase['label'],
                'earning',
                $increase['amount'],
                'salary_increase',
                11 + $index
            );
        }
        $lines = [
            ...$lines,
            $this->payslipLine('PRIME_SPECIALE', 'Prime spéciale', 'earning', 20000, 'payroll_reference', 20),
            $this->payslipLine('INDEMNITE_COMPENSATION', 'Indemnité de compensation', 'earning', 60000, 'payroll_reference', 30),
            $this->payslipLine('IRD', 'Indice de Recherche et de Documentation (IRD)', 'earning', 70000, 'payroll_reference', 40),
            $this->payslipLine('IPRES_SALARIE', 'IPRES — part salariale', 'contribution', 14336, 'payroll_profile', 80),
            $this->payslipLine('IPM', 'IPM', 'contribution', 4500, 'payroll_profile', 85),
            $this->payslipLine('IMPR', 'Impôt mensuel sur le revenu (IMPR)', 'deduction', 11767, 'payroll_profile', 90),
            $this->payslipLine('TRIMF', 'Taxe représentative de l’impôt du minimum fiscal (TRIMF)', 'deduction', 500, 'payroll_profile', 95),
            $this->payslipLine('CHECKOFF_UES', 'Check-off UES', 'deduction', 1000, 'payroll_profile', 96),
            $this->payslipLine('TABASKI_RETENUE', 'Retenue Tabaski', 'deduction', 10000, 'manual', 97),
            $this->payslipLine('IPRES_EMPLOYEUR', 'IPRES — part employeur', 'employer_contribution', 21504, 'payroll_reference', 100),
        ];
        $gross = $salary + 150000;
        $deductions = 42103;

        return [
            'gross' => $gross,
            'deductions' => $deductions,
            'employer' => 21504,
            'net' => $gross - $deductions,
            'profile' => [
                'engagement_type' => 'contractuel',
                'diploma_label' => 'BAC / BT',
                'category_level' => $teacher['category'],
                'salary_origin' => $salaryOrigin,
                'salary_increases' => $increases->values()->all(),
                'calculation_model' => 'sicore-pc-vacataire-v1',
            ],
            'lines' => $lines,
        ];
    }

    /** @return array<string, mixed> */
    private function payslipLine(
        string $code,
        string $label,
        string $category,
        float|int $amount,
        string $source,
        int $sortOrder
    ): array {
        return [
            'code' => $code,
            'label' => $label,
            'category' => $category,
            'amount' => $amount,
            'source' => $source,
            'sort_order' => $sortOrder,
        ];
    }

    /** @param array<string, mixed> $values */
    private function referenceId(string $table, array $values): int
    {
        $existing = DB::table($table)->where('code', $values['code'])->first();

        if ($existing) {
            return (int) $existing->id;
        }

        return (int) DB::table($table)->insertGetId([
            ...$values,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /** @param array<string, mixed> $values */
    private function teacherId(array $values): int
    {
        $existing = DB::table('enseignants')->where('matricule', $values['matricule'])->first();

        if ($existing) {
            DB::table('enseignants')->where('id', $existing->id)->update([
                ...$values,
                'updated_at' => now(),
            ]);

            return (int) $existing->id;
        }

        return (int) DB::table('enseignants')->insertGetId([
            ...$values,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
