<?php

namespace Database\Seeders;

use App\Models\PayrollAttendance;
use App\Models\PayrollPeriod;
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
            }
        });
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
