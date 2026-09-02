<?php

namespace Database\Seeders;

use App\Models\Enseignant;
use App\Models\PayrollAttendance;
use App\Models\PayrollElement;
use App\Models\PayrollPeriod;
use App\Models\User;
use App\Services\PayrollCalculationService;
use App\Services\PayrollReferenceService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PayrollPresentationSeeder extends Seeder
{
    public function run(): void
    {
        $this->normalizeExistingReferenceData();
        $timestamps = ['created_at' => now(), 'updated_at' => now()];
        $adminRole = $this->reference('roles', ['libelle' => 'Administrateur'], $timestamps);
        $teacherRole = $this->reference('roles', ['libelle' => 'Enseignant'], $timestamps);
        $category = $this->reference('categories', ['libelle' => 'Personnel enseignant'], $timestamps);
        $contractCorps = $this->reference(
            'corps_enseignants',
            ['libelle' => 'Professeurs contractuels', 'categorie_id' => $category],
            $timestamps
        );
        $vacataireCorps = $this->reference(
            'corps_enseignants',
            ['libelle' => 'Vacataires', 'categorie_id' => $category],
            $timestamps
        );
        $bank = $this->reference(
            'institution_financieres',
            ['nom' => 'CBAO Groupe Attijariwafa bank'],
            $timestamps,
            'nom'
        );
        $bicisBank = $this->reference(
            'institution_financieres',
            ['nom' => 'BICIS'],
            $timestamps,
            'nom'
        );
        $boaBank = $this->reference(
            'institution_financieres',
            ['nom' => 'Bank of Africa Sénégal'],
            $timestamps,
            'nom'
        );
        $sgbsBank = $this->reference(
            'institution_financieres',
            ['nom' => 'Société Générale Sénégal'],
            $timestamps,
            'nom'
        );
        $mutuelleEnseignants = $this->reference(
            'mutuelles',
            ['nom' => 'Mutuelle de santé des enseignants'],
            $timestamps,
            'nom'
        );
        $mutuelleAgents = $this->reference(
            'mutuelles',
            ['nom' => 'Mutuelle complémentaire des agents'],
            $timestamps,
            'nom'
        );

        $saintLouisIa = $this->reference(
            'ias',
            ['code' => 'IA-SL', 'libelle' => 'IA Saint-Louis', 'centre_geo' => 'Saint-Louis'],
            $timestamps,
            'code'
        );
        $saintLouisIef = $this->reference(
            'iefs',
            [
                'code' => 'IEF-SLC',
                'libelle' => 'IEF Saint-Louis Commune',
                'departement' => 'Saint-Louis',
                'ia_id' => $saintLouisIa,
            ],
            $timestamps,
            'code'
        );
        $saintLouisSchool = $this->reference(
            'etablissements',
            [
                'code' => 'ETAB-SLC',
                'libelle' => 'Centre de formation professionnelle de Saint-Louis',
                'niveau' => 'secondaire',
                'ief_id' => $saintLouisIef,
            ],
            $timestamps,
            'code'
        );

        $thiesIa = $this->reference(
            'ias',
            ['code' => 'IA-TH', 'libelle' => 'IA Thiès', 'centre_geo' => 'Thiès'],
            $timestamps,
            'code'
        );
        $thiesIef = $this->reference(
            'iefs',
            [
                'code' => 'IEF-THV',
                'libelle' => 'IEF Thiès Ville',
                'departement' => 'Thiès',
                'ia_id' => $thiesIa,
            ],
            $timestamps,
            'code'
        );
        $thiesSchool = $this->reference(
            'etablissements',
            [
                'code' => 'CNAFP',
                'libelle' => 'CNAFP — Centre national d’appui à la formation professionnelle',
                'niveau' => 'secondaire',
                'ief_id' => $thiesIef,
            ],
            $timestamps,
            'code'
        );

        $dakarIa = $this->reference(
            'ias',
            ['code' => 'IA-DK', 'libelle' => 'IA Dakar', 'centre_geo' => 'Dakar'],
            $timestamps,
            'code'
        );
        $dakarIef = $this->reference(
            'iefs',
            [
                'code' => 'IEF-DKPL',
                'libelle' => 'IEF Dakar Plateau',
                'departement' => 'Dakar',
                'ia_id' => $dakarIa,
            ],
            $timestamps,
            'code'
        );
        $dakarSchool = $this->reference(
            'etablissements',
            [
                'code' => 'CFP-DKPL',
                'libelle' => 'Centre de formation professionnelle de Dakar Plateau',
                'niveau' => 'secondaire',
                'ief_id' => $dakarIef,
            ],
            $timestamps,
            'code'
        );

        $kaolackIa = $this->reference(
            'ias',
            ['code' => 'IA-KL', 'libelle' => 'IA Kaolack', 'centre_geo' => 'Kaolack'],
            $timestamps,
            'code'
        );
        $kaolackIef = $this->reference(
            'iefs',
            [
                'code' => 'IEF-KLC',
                'libelle' => 'IEF Kaolack Commune',
                'departement' => 'Kaolack',
                'ia_id' => $kaolackIa,
            ],
            $timestamps,
            'code'
        );
        $kaolackSchool = $this->reference(
            'etablissements',
            [
                'code' => 'CFP-KLC',
                'libelle' => 'Centre de formation professionnelle de Kaolack',
                'niveau' => 'secondaire',
                'ief_id' => $kaolackIef,
            ],
            $timestamps,
            'code'
        );

        $admin = User::query()->updateOrCreate(
            ['email' => 'admin@sicore.sn'],
            [
                'nom' => 'SICORE',
                'prenom' => 'Administrateur',
                'password' => 'Sicore@2026',
                'role_id' => $adminRole,
                'login_enabled' => true,
            ]
        );

        $vacataire = Enseignant::query()->updateOrCreate(
            ['matricule' => 'VAC-2026-001'],
            [
                'type_engagement' => 'vacataire',
                'salaire_base' => 150000,
                'nombre_parts' => 1,
                'impr_monthly_amount' => 10500,
                'trimf_monthly_amount' => 400,
                'ipm_monthly_amount' => 0,
                'union_checkoff_monthly_amount' => 0,
                'actif' => true,
                'numero_compte' => 'SN-VAC-2026-0001',
                'corps_enseignant_id' => $vacataireCorps,
                'institution_financiere_id' => $bank,
                'etablissement_id' => $saintLouisSchool,
                'payroll_profile_configured_at' => now(),
                'payroll_profile_configured_by' => $admin->id,
            ]
        );
        $this->teacherAccount(
            $vacataire,
            'Oumou',
            'DIOP',
            'paie.vacataire.2026@sicore.local',
            $teacherRole
        );

        $contractuel = Enseignant::query()->updateOrCreate(
            ['matricule' => 'PC-2026-001'],
            [
                'type_engagement' => 'contractuel',
                'payroll_diploma_level' => 'BAC_BT',
                'payroll_category_level' => 1,
                'diplome' => 'BAC / BT',
                'salaire_base' => 152773,
                'nombre_parts' => 1,
                'impr_monthly_amount' => 11767,
                'trimf_monthly_amount' => 500,
                'ipm_monthly_amount' => 4500,
                'union_checkoff_monthly_amount' => 1000,
                'actif' => true,
                'numero_compte' => 'SN-PC-2026-0001',
                'corps_enseignant_id' => $contractCorps,
                'institution_financiere_id' => $bank,
                'etablissement_id' => $thiesSchool,
                'payroll_profile_configured_at' => now(),
                'payroll_profile_configured_by' => $admin->id,
            ]
        );
        $this->teacherAccount(
            $contractuel,
            'Oumy',
            'DIOP',
            'paie.contractuel.2026@sicore.local',
            $teacherRole
        );

        /*
         * La recette contient vingt agents : dix vacataires et dix
         * contractuels. Ils sont répartis sur quatre IA/IEF et quatre banques
         * afin que la recherche, les tableaux individuels et les agrégats
         * puissent tous être testés avec des données réalistes.
         */
        $teachers = [
            $vacataire,
            $contractuel,
            ...$this->additionalTeachers(
                [
                    ['school_id' => $saintLouisSchool, 'bank_id' => $bank],
                    ['school_id' => $thiesSchool, 'bank_id' => $bicisBank],
                    ['school_id' => $dakarSchool, 'bank_id' => $boaBank],
                    ['school_id' => $kaolackSchool, 'bank_id' => $sgbsBank],
                ],
                $vacataireCorps,
                $contractCorps,
                $teacherRole,
                $admin
            ),
        ];

        foreach ($teachers as $index => $teacher) {
            $teacher->update([
                'mutuelle_id' => $index % 3 === 0 ? $mutuelleAgents : $mutuelleEnseignants,
            ]);
        }

        $period = PayrollPeriod::query()->firstOrNew(['code' => '2026-03']);
        $period->fill([
            'label' => 'Mars 2026',
            'start_date' => '2026-03-01',
            'end_date' => '2026-03-31',
        ]);
        if (! $period->exists) {
            $period->status = PayrollPeriod::STATUS_OPEN;
            $period->version = 1;
        }
        $period->save();

        foreach ($teachers as $teacher) {
            $teacher->loadMissing('etablissement.ief');
            $teacherIef = $teacher->etablissement?->ief;
            PayrollAttendance::query()->updateOrCreate(
                [
                    'payroll_period_id' => $period->id,
                    'enseignant_id' => $teacher->id,
                ],
                [
                    'absence_days' => 0,
                    'delay_minutes' => 0,
                    'deduction_amount' => 0,
                    'status' => 'validated',
                    'validated_by' => $admin->id,
                    'validated_at' => now(),
                    'notes' => 'Présence contrôlée pour la période de mars 2026.',
                    'version' => 1,
                ]
            );
            PayrollElement::query()->updateOrCreate(
                [
                    'payroll_period_id' => $period->id,
                    'enseignant_id' => $teacher->id,
                    'code' => 'TABASKI_RETENUE',
                ],
                [
                    'label' => 'Retenue Tabaski',
                    'category' => 'deduction',
                    'source' => 'manual',
                    'amount' => 10000,
                    'academic_year' => '2025-2026',
                    'application_scope' => 'collective',
                    'application_reference' => sprintf(
                        'TABASKI_RETENUE-202603-%s-IA%d-IEF%d',
                        strtoupper((string) $teacher->type_engagement),
                        (int) $teacherIef?->ia_id,
                        (int) $teacherIef?->id
                    ),
                    'application_ia_id' => $teacherIef?->ia_id,
                    'application_ief_id' => $teacherIef?->id,
                    'applied_at' => now(),
                    'applied_by' => $admin->id,
                    'status' => 'validated',
                    'created_by' => $admin->id,
                    'validated_by' => $admin->id,
                    'validated_at' => now(),
                    'version' => 1,
                ]
            );
        }

        $period->elements()
            ->where('code', '!=', 'TABASKI_RETENUE')
            ->where('is_exempt', false)
            ->update([
                'is_exempt' => true,
                'exemption_reason' => 'Élément conservé et neutralisé pour le bulletin de référence de mars 2026.',
                'status' => 'validated',
                'validated_by' => $admin->id,
                'validated_at' => now(),
                'updated_at' => now(),
            ]);

        /*
         * Si une ancienne base de recette ne contenait encore que les deux
         * bulletins historiques, ce seeder reconstruit uniquement la paie de
         * mars pour intégrer les vingt agents.
         */
        $expectedIncreaseLines = collect($teachers)
            ->where('type_engagement', PayrollReferenceService::CONTRACTUEL)
            ->count() * count(config('payroll_reference.contract_salary_increases', []));
        $actualIncreaseLines = DB::table('payroll_payslip_lines')
            ->join('payroll_payslips', 'payroll_payslips.id', '=', 'payroll_payslip_lines.payroll_payslip_id')
            ->where('payroll_payslips.payroll_period_id', $period->id)
            ->where('payroll_payslip_lines.source', 'salary_increase')
            ->count();
        $requiresPayslipRebuild = $period->payslips()->count() !== count($teachers)
            || $actualIncreaseLines !== $expectedIncreaseLines;

        if ($period->run()->exists() && $requiresPayslipRebuild) {
            $period->run()->delete();
            $period->update([
                'status' => PayrollPeriod::STATUS_OPEN,
                'validated_by' => null,
                'validated_at' => null,
                'version' => $period->version + 1,
            ]);
        }

        if (! $period->run()->exists()) {
            $run = app(PayrollCalculationService::class)->calculate($period->fresh(), $admin);
            $run->update([
                'status' => 'validated',
                'validated_by' => $admin->id,
                'validated_at' => now(),
            ]);
            $period->refresh()->update([
                'status' => PayrollPeriod::STATUS_VALIDATED,
                'validated_by' => $admin->id,
                'validated_at' => now(),
                'version' => $period->version + 1,
            ]);
        }

        foreach ($period->payslips()->orderBy('id')->get() as $index => $payslip) {
            if ($payslip->payment_status === 'paid') {
                continue;
            }
            $payslip->update([
                'payment_status' => 'paid',
                'payment_reference' => 'VIR-202603-'.str_pad((string) ($index + 1), 4, '0', STR_PAD_LEFT),
                'paid_at' => now(),
                'version' => $payslip->version + 1,
            ]);
        }

        $openPeriod = PayrollPeriod::query()->firstOrCreate(
            ['code' => '2026-04'],
            [
                'label' => 'Avril 2026',
                'start_date' => '2026-04-01',
                'end_date' => '2026-04-30',
                'status' => PayrollPeriod::STATUS_OPEN,
                'version' => 1,
            ]
        );

        foreach ($teachers as $index => $teacher) {
            $hasAbsence = $index > 1 && $index % 4 === 0;
            PayrollAttendance::query()->updateOrCreate(
                [
                    'payroll_period_id' => $openPeriod->id,
                    'enseignant_id' => $teacher->id,
                ],
                [
                    'absence_days' => $hasAbsence ? 1 : 0,
                    'delay_minutes' => ($index % 5) * 8,
                    'deduction_amount' => $hasAbsence ? 5000 : 0,
                    'status' => 'draft',
                    'notes' => $hasAbsence
                        ? 'Une journée d’absence à contrôler avant validation.'
                        : 'État à contrôler avant le calcul de la paie.',
                    'version' => 1,
                ]
            );
            $this->openPeriodElement($openPeriod, $teacher, $admin, [
                'code' => 'TABASKI_RETENUE',
                'label' => 'Retenue Tabaski',
                'category' => 'deduction',
                'amount' => 10000,
            ]);
            $this->openPeriodElement($openPeriod, $teacher, $admin, [
                'code' => 'TABASKI_AVANCE',
                'label' => 'Avance Tabaski',
                'category' => 'earning',
                'amount' => 5000 + (($index % 4) * 1000),
            ]);
            $this->openPeriodElement($openPeriod, $teacher, $admin, [
                'code' => 'RAPPEL_RETENUE',
                'label' => 'Retenue sur rappel',
                'category' => 'deduction',
                'amount' => 1000 + (($index % 5) * 250),
            ]);
            $this->openPeriodElement($openPeriod, $teacher, $admin, [
                'code' => 'PRIME_EXEMPTEE',
                'label' => 'Prime neutralisée pour recette',
                'category' => 'earning',
                'amount' => 2500 + (($index % 3) * 500),
                'is_exempt' => true,
                'exemption_reason' => 'Élément neutralisé pour tester la page des exemptions.',
            ]);
        }
    }

    /**
     * Crée les dix-huit agents complémentaires de la base de recette.
     *
     * @param  array<int, array{school_id: int, bank_id: int}>  $placements
     * @return array<int, Enseignant>
     */
    private function additionalTeachers(
        array $placements,
        int $vacataireCorps,
        int $contractCorps,
        int $teacherRole,
        User $admin
    ): array {
        $people = [
            ['vacataire', 'Awa', 'NDIAYE'],
            ['contractuel', 'Mamadou', 'DIALLO'],
            ['vacataire', 'Fatou', 'FALL'],
            ['contractuel', 'Ibrahima', 'FAYE'],
            ['vacataire', 'Mariama', 'BA'],
            ['contractuel', 'Cheikh', 'SARR'],
            ['vacataire', 'Khady', 'DIOUF'],
            ['contractuel', 'Abdou Aziz', 'NDIAYE'],
            ['vacataire', 'Aminata', 'SOW'],
            ['contractuel', 'Pape', 'FALL'],
            ['vacataire', 'Ndeye', 'SECK'],
            ['contractuel', 'Serigne', 'MBACKE'],
            ['vacataire', 'Rokhaya', 'GUEYE'],
            ['contractuel', 'Moussa', 'CISSE'],
            ['vacataire', 'Astou', 'KANE'],
            ['contractuel', 'Babacar', 'SY'],
            ['vacataire', 'Adama', 'MBAYE'],
            ['contractuel', 'Alioune', 'DIOP'],
        ];
        $diplomas = ['CAP', 'BEP', 'BAC_BT', 'BTS_DUEL_DUES', 'LICENCE', 'MASTER_MAITRISE'];
        $diplomaLabels = config('payroll_reference.diplomas');
        $references = app(PayrollReferenceService::class);
        $vacataireNumber = 1;
        $contractNumber = 1;
        $teachers = [];

        foreach ($people as $index => [$engagement, $firstName, $lastName]) {
            $isContract = $engagement === PayrollReferenceService::CONTRACTUEL;
            $sequence = $isContract ? ++$contractNumber : ++$vacataireNumber;
            $prefix = $isContract ? 'PC' : 'VAC';
            $diploma = $isContract ? $diplomas[($sequence - 2) % count($diplomas)] : null;
            $category = $isContract ? min(12, $sequence) : null;
            $salary = $references->salaryFor($engagement, $diploma, $category, '2026-03-31');
            // Chaque IA/IEF reçoit les deux populations pour faciliter la recette.
            $placement = $placements[intdiv($index, 2) % count($placements)];
            $matricule = sprintf('%s-2026-%03d', $prefix, $sequence);

            $teacher = Enseignant::query()->updateOrCreate(
                ['matricule' => $matricule],
                [
                    'type_engagement' => $engagement,
                    'payroll_diploma_level' => $diploma,
                    'payroll_category_level' => $category,
                    'diplome' => $diploma ? ($diplomaLabels[$diploma] ?? $diploma) : null,
                    'salaire_base' => $salary,
                    'nombre_parts' => 1 + ($index % 3),
                    'impr_monthly_amount' => $isContract ? 9500 + ($index * 225) : 8500 + ($index * 125),
                    'trimf_monthly_amount' => $isContract ? 500 : 400,
                    'ipm_monthly_amount' => $isContract ? 4500 : 0,
                    'union_checkoff_monthly_amount' => $isContract ? 1000 : 0,
                    'actif' => true,
                    'numero_compte' => sprintf('SN-%s-2026-%04d', $prefix, $sequence),
                    'corps_enseignant_id' => $isContract ? $contractCorps : $vacataireCorps,
                    'institution_financiere_id' => $placement['bank_id'],
                    'etablissement_id' => $placement['school_id'],
                    'payroll_profile_configured_at' => now(),
                    'payroll_profile_configured_by' => $admin->id,
                ]
            );
            $this->teacherAccount(
                $teacher,
                $firstName,
                $lastName,
                sprintf('paie.%s.%03d@sicore.local', $engagement, $sequence),
                $teacherRole
            );
            $teachers[] = $teacher;
        }

        return $teachers;
    }

    /** @param array<string, mixed> $values */
    private function openPeriodElement(
        PayrollPeriod $period,
        Enseignant $teacher,
        User $admin,
        array $values
    ): void {
        $isTabaski = in_array($values['code'], ['TABASKI_AVANCE', 'TABASKI_RETENUE'], true);
        $teacher->loadMissing('etablissement.ief');
        $teacherIef = $teacher->etablissement?->ief;

        PayrollElement::query()->updateOrCreate(
            [
                'payroll_period_id' => $period->id,
                'enseignant_id' => $teacher->id,
                'code' => $values['code'],
            ],
            [
                'label' => $values['label'],
                'category' => $values['category'],
                'source' => 'manual',
                'amount' => $values['amount'],
                'academic_year' => $isTabaski ? '2025-2026' : null,
                'application_scope' => $isTabaski ? 'collective' : 'individual',
                'application_reference' => $isTabaski
                    ? sprintf(
                        '%s-202604-%s-IA%d-IEF%d',
                        $values['code'],
                        strtoupper((string) $teacher->type_engagement),
                        (int) $teacherIef?->ia_id,
                        (int) $teacherIef?->id
                    )
                    : null,
                'application_ia_id' => $isTabaski ? $teacherIef?->ia_id : null,
                'application_ief_id' => $isTabaski ? $teacherIef?->id : null,
                'applied_at' => $isTabaski ? now() : null,
                'applied_by' => $isTabaski ? $admin->id : null,
                'is_exempt' => $values['is_exempt'] ?? false,
                'exemption_reason' => $values['exemption_reason'] ?? null,
                'status' => 'draft',
                'created_by' => $admin->id,
                'validated_by' => null,
                'validated_at' => null,
                'version' => 1,
            ]
        );
    }

    private function teacherAccount(
        Enseignant $teacher,
        string $firstName,
        string $lastName,
        string $email,
        int $roleId
    ): void {
        User::query()->updateOrCreate(
            ['enseignant_id' => $teacher->id],
            [
                'email' => $email,
                'nom' => $lastName,
                'prenom' => $firstName,
                'password' => bin2hex(random_bytes(32)),
                'role_id' => $roleId,
                'enseignant_id' => $teacher->id,
                'login_enabled' => false,
            ]
        );
    }

    private function normalizeExistingReferenceData(): void
    {
        $this->renameCode('ias', 'IA-SL-DEMO', 'IA-SL', 'IA Saint-Louis');
        $this->renameCode('iefs', 'IEF-SLC-DEMO', 'IEF-SLC', 'IEF Saint-Louis Commune');
        $this->renameCode(
            'etablissements',
            'ETAB-SLC-DEMO',
            'ETAB-SLC',
            'Centre de formation professionnelle de Saint-Louis'
        );
        $this->renameCode('ias', 'IA-TH-DEMO', 'IA-TH', 'IA Thiès');
        $this->renameCode('iefs', 'IEF-THV-DEMO', 'IEF-THV', 'IEF Thiès Ville');
        $this->renameCode(
            'etablissements',
            'CNAFP-DEMO',
            'CNAFP',
            'CNAFP — Centre national d’appui à la formation professionnelle'
        );

        $this->renameMatricule('DEMO-VAC-001', 'VAC-2026-001');
        $this->renameMatricule('DEMO-PC-001', 'PC-2026-001');
    }

    private function renameCode(
        string $table,
        string $oldCode,
        string $newCode,
        string $label
    ): void {
        $old = DB::table($table)->where('code', $oldCode)->first();
        if (! $old) {
            return;
        }

        if (! DB::table($table)->where('code', $newCode)->exists()) {
            DB::table($table)->where('code', $oldCode)->update([
                'code' => $newCode,
                'libelle' => $label,
                'updated_at' => now(),
            ]);
        } else {
            DB::table($table)->where('code', $oldCode)->update([
                'libelle' => $label,
                'updated_at' => now(),
            ]);
        }
    }

    private function renameMatricule(string $oldMatricule, string $newMatricule): void
    {
        if (Enseignant::query()->where('matricule', $newMatricule)->exists()) {
            return;
        }

        Enseignant::query()->where('matricule', $oldMatricule)->update([
            'matricule' => $newMatricule,
            'updated_at' => now(),
        ]);
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
