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
                ['matricule' => 'VAC-TEST-001', 'prenom' => 'Awa', 'nom' => 'Ndiaye', 'corps' => 'VAC', 'salary' => 150000],
                ['matricule' => 'PC-TEST-001', 'prenom' => 'Moussa', 'nom' => 'Diop', 'corps' => 'PC', 'salary' => 225000],
                ['matricule' => 'VAC-TEST-002', 'prenom' => 'Fatou', 'nom' => 'Sarr', 'corps' => 'VAC', 'salary' => 155000],
                ['matricule' => 'PC-TEST-002', 'prenom' => 'Ibrahima', 'nom' => 'Fall', 'corps' => 'PC', 'salary' => 235000],
                ['matricule' => 'VAC-TEST-003', 'prenom' => 'Mariama', 'nom' => 'Ba', 'corps' => 'VAC', 'salary' => 160000],
                ['matricule' => 'PC-TEST-003', 'prenom' => 'Cheikh', 'nom' => 'Diallo', 'corps' => 'PC', 'salary' => 245000],
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
                $inspection = $inspections[$index];
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
            $this->seedPaidPayslips($period, $seededTeachers);
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
        $totalGross = array_sum(array_column($teachers, 'salary'));
        $totalDeductions = count($teachers) * 10000;
        $totalNet = $totalGross - $totalDeductions;

        $run = PayrollRun::query()->firstOrCreate(
            ['payroll_period_id' => $period->id],
            [
                'reference' => 'PAY-TEST-'.$period->code,
                'status' => 'validated',
                'employee_count' => count($teachers),
                'total_gross' => $totalGross,
                'total_deductions' => $totalDeductions,
                'total_employer_contributions' => 0,
                'total_net' => $totalNet,
                'checksum' => hash('sha256', 'PAY-TEST-'.$period->code),
                'calculated_at' => now(),
                'validated_at' => now(),
            ]
        );

        foreach ($teachers as $index => $teacher) {
            $gross = (float) $teacher['salary'];
            $deductions = 10000.0;
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
                    'gross_amount' => $gross,
                    'deduction_amount' => $deductions,
                    'employer_contribution_amount' => 0,
                    'net_amount' => $gross - $deductions,
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

            $payslip->lines()->firstOrCreate(
                ['code' => 'SALAIRE_BASE_TEST'],
                [
                    'label' => 'Salaire de base de démonstration',
                    'category' => 'earning',
                    'amount' => $gross,
                    'source' => 'test_seeder',
                    'sort_order' => 10,
                ]
            );
            $payslip->lines()->firstOrCreate(
                ['code' => 'TABASKI_RETENUE_TEST'],
                [
                    'label' => 'Retenue Tabaski de démonstration',
                    'category' => 'deduction',
                    'amount' => $deductions,
                    'source' => 'test_seeder',
                    'sort_order' => 20,
                ]
            );
        }

        if ((int) $period->employee_count === 0) {
            $period->update([
                'employee_count' => count($teachers),
                'total_gross' => $totalGross,
                'total_deductions' => $totalDeductions,
                'total_net' => $totalNet,
            ]);
        }
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
            return (int) $existing->id;
        }

        return (int) DB::table('enseignants')->insertGetId([
            ...$values,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
