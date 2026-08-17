<?php

namespace Database\Seeders;

use App\Models\Enseignant;
use App\Models\PayrollAttendance;
use App\Models\PayrollElement;
use App\Models\PayrollPeriod;
use App\Models\User;
use App\Services\PayrollCalculationService;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PayrollDemoSeeder extends Seeder
{
    public function run(): void
    {
        $now = CarbonImmutable::now();
        $timestamps = ['created_at' => $now, 'updated_at' => $now];

        $adminRole = $this->reference('roles', ['libelle' => 'Administrateur'], $timestamps);
        $teacherRole = $this->reference('roles', ['libelle' => 'Enseignant'], $timestamps);
        $category = $this->reference('categories', ['libelle' => 'Enseignement général'], $timestamps);
        $corps = $this->reference(
            'corps_enseignants',
            ['libelle' => 'Professeur', 'categorie_id' => $category],
            $timestamps
        );
        $ia = $this->reference(
            'ias',
            ['code' => 'IA-DKR', 'libelle' => 'IA Dakar', 'centre_geo' => 'Dakar'],
            $timestamps,
            'code'
        );
        $ief = $this->reference(
            'iefs',
            ['code' => 'IEF-DN', 'libelle' => 'IEF Dakar Nord', 'ia_id' => $ia],
            $timestamps,
            'code'
        );
        $school = $this->reference(
            'etablissements',
            [
                'code' => 'ETAB-001',
                'libelle' => 'Centre de formation SICORE',
                'niveau' => 'secondaire',
                'ief_id' => $ief,
            ],
            $timestamps,
            'code'
        );
        $banks = [
            $this->reference('institution_financieres', ['nom' => 'Banque de l’Habitat du Sénégal'], $timestamps, 'nom'),
            $this->reference('institution_financieres', ['nom' => 'CBAO Groupe Attijariwafa bank'], $timestamps, 'nom'),
            $this->reference('institution_financieres', ['nom' => 'La Banque Agricole'], $timestamps, 'nom'),
        ];

        $admin = User::query()->updateOrCreate(
            ['email' => 'admin@sicore.sn'],
            [
                'nom' => 'SICORE',
                'prenom' => 'Administrateur',
                'password' => 'Sicore@2026',
                'role_id' => $adminRole,
            ]
        );

        $teachers = collect([
            ['matricule' => 'SIC-0001', 'prenom' => 'Mamadou', 'nom' => 'DIOP', 'salary' => 485000, 'bank' => 0],
            ['matricule' => 'SIC-0002', 'prenom' => 'Aïssatou', 'nom' => 'NDIAYE', 'salary' => 512000, 'bank' => 1],
            ['matricule' => 'SIC-0003', 'prenom' => 'Cheikh', 'nom' => 'FALL', 'salary' => 455000, 'bank' => 0],
            ['matricule' => 'SIC-0004', 'prenom' => 'Fatou', 'nom' => 'SARR', 'salary' => 538000, 'bank' => 2],
            ['matricule' => 'SIC-0005', 'prenom' => 'Ibrahima', 'nom' => 'BA', 'salary' => 467500, 'bank' => 1],
            ['matricule' => 'SIC-0006', 'prenom' => 'Mariama', 'nom' => 'DIALLO', 'salary' => 496000, 'bank' => 2],
        ])->map(function (array $item, int $index) use (
            $banks,
            $corps,
            $school,
            $teacherRole
        ): Enseignant {
            $teacher = Enseignant::query()->updateOrCreate(
                ['matricule' => $item['matricule']],
                [
                    'indice' => (string) (500 + ($index * 25)),
                    'date_recrutement' => now()->subYears(4 + $index)->toDateString(),
                    'salaire_base' => $item['salary'],
                    'nombre_parts' => ($index % 3) + 1,
                    'actif' => true,
                    'numero_compte' => 'SN08-'.str_pad((string) ($index + 1), 16, '0', STR_PAD_LEFT),
                    'corps_enseignant_id' => $corps,
                    'institution_financiere_id' => $banks[$item['bank']],
                    'etablissement_id' => $school,
                ]
            );

            User::query()->updateOrCreate(
                ['email' => mb_strtolower($item['prenom'].'.'.$item['nom']).'@sicore.sn'],
                [
                    'nom' => $item['nom'],
                    'prenom' => $item['prenom'],
                    'password' => 'Sicore@2026',
                    'role_id' => $teacherRole,
                    'enseignant_id' => $teacher->id,
                ]
            );

            return $teacher;
        });

        $currentStart = $now->startOfMonth();
        $previousStart = $currentStart->subMonth();
        $current = PayrollPeriod::query()->firstOrCreate(
            ['code' => $currentStart->format('Y-m')],
            [
                'label' => ucfirst($currentStart->locale('fr')->translatedFormat('F Y')),
                'start_date' => $currentStart->toDateString(),
                'end_date' => $currentStart->endOfMonth()->toDateString(),
                'status' => PayrollPeriod::STATUS_OPEN,
            ]
        );
        $previous = PayrollPeriod::query()->firstOrCreate(
            ['code' => $previousStart->format('Y-m')],
            [
                'label' => ucfirst($previousStart->locale('fr')->translatedFormat('F Y')),
                'start_date' => $previousStart->toDateString(),
                'end_date' => $previousStart->endOfMonth()->toDateString(),
                'status' => PayrollPeriod::STATUS_OPEN,
            ]
        );

        foreach ($teachers as $index => $teacher) {
            PayrollAttendance::query()->updateOrCreate(
                [
                    'payroll_period_id' => $current->id,
                    'enseignant_id' => $teacher->id,
                ],
                [
                    'absence_days' => $index % 3,
                    'delay_minutes' => $index * 12,
                    'deduction_amount' => round(
                        (((float) $teacher->salaire_base / 30) * ($index % 3))
                        + (((float) $teacher->salaire_base / 30 / 480) * ($index * 12)),
                        2
                    ),
                    'status' => 'draft',
                    'notes' => $index % 3 ? 'État à contrôler avant validation.' : null,
                    'version' => 1,
                ]
            );
            PayrollAttendance::query()->updateOrCreate(
                [
                    'payroll_period_id' => $previous->id,
                    'enseignant_id' => $teacher->id,
                ],
                [
                    'absence_days' => $index % 2,
                    'delay_minutes' => $index * 5,
                    'deduction_amount' => round(
                        (((float) $teacher->salaire_base / 30) * ($index % 2))
                        + (((float) $teacher->salaire_base / 30 / 480) * ($index * 5)),
                        2
                    ),
                    'status' => 'validated',
                    'validated_by' => $admin->id,
                    'validated_at' => $previousStart->endOfMonth(),
                    'version' => 1,
                ]
            );

            PayrollElement::query()->updateOrCreate(
                [
                    'payroll_period_id' => $current->id,
                    'enseignant_id' => $teacher->id,
                    'code' => 'TABASKI_AVANCE',
                ],
                [
                    'label' => 'Avance Tabaski',
                    'category' => 'earning',
                    'source' => 'manual',
                    'amount' => 50000 + ($index * 5000),
                    'status' => 'draft',
                    'created_by' => $admin->id,
                    'version' => 1,
                ]
            );
            PayrollElement::query()->updateOrCreate(
                [
                    'payroll_period_id' => $previous->id,
                    'enseignant_id' => $teacher->id,
                    'code' => 'PRIME_RENDEMENT',
                ],
                [
                    'label' => 'Prime de rendement',
                    'category' => 'earning',
                    'source' => 'manual',
                    'amount' => 35000 + ($index * 2500),
                    'status' => 'validated',
                    'created_by' => $admin->id,
                    'validated_by' => $admin->id,
                    'validated_at' => $previousStart->endOfMonth(),
                    'version' => 1,
                ]
            );
        }

        if (! $previous->run()->exists() && $previous->status === PayrollPeriod::STATUS_OPEN) {
            $run = app(PayrollCalculationService::class)->calculate($previous, $admin);
            $run->update([
                'status' => 'closed',
                'validated_by' => $admin->id,
                'validated_at' => $previousStart->endOfMonth(),
            ]);
            $previous->refresh()->update([
                'status' => PayrollPeriod::STATUS_CLOSED,
                'validated_by' => $admin->id,
                'validated_at' => $previousStart->endOfMonth(),
                'closed_by' => $admin->id,
                'closed_at' => $previousStart->endOfMonth()->addHours(2),
                'version' => $previous->version + 2,
            ]);
            $run->payslips()->take(4)->get()->each(function ($payslip, int $index) use ($previousStart): void {
                $payslip->update([
                    'payment_status' => 'paid',
                    'payment_reference' => 'VIR-'.$previousStart->format('Ym').'-'.str_pad((string) ($index + 1), 4, '0', STR_PAD_LEFT),
                    'paid_at' => $previousStart->endOfMonth()->addDay(),
                    'version' => 2,
                ]);
            });
        }
    }

    private function reference(
        string $table,
        array $values,
        array $timestamps,
        string $key = 'libelle'
    ): int {
        DB::table($table)->updateOrInsert(
            [$key => $values[$key]],
            [...$values, ...$timestamps]
        );

        return (int) DB::table($table)->where($key, $values[$key])->value('id');
    }
}
