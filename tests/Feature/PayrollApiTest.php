<?php

namespace Tests\Feature;

use App\Models\Enseignant;
use App\Models\PayrollAttendance;
use App\Models\PayrollElement;
use App\Models\PayrollPayslip;
use App\Models\PayrollPeriod;
use App\Models\roles;
use App\Models\User;
use Carbon\CarbonImmutable;
use Database\Seeders\GestionPaieSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PayrollApiTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Enseignant $teacher;

    private int $iaId;

    private int $iefId;

    protected function setUp(): void
    {
        parent::setUp();

        $role = roles::query()->create(['libelle' => 'Administrateur']);
        $this->admin = User::query()->create([
            'nom' => 'SICORE',
            'prenom' => 'Admin',
            'email' => 'admin@sicore.sn',
            'password' => 'Sicore@2026',
            'role_id' => $role->id,
        ]);
        $this->iaId = DB::table('ias')->insertGetId([
            'code' => 'IA-TEST',
            'libelle' => 'IA Test',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->iefId = DB::table('iefs')->insertGetId([
            'code' => 'IEF-TEST',
            'libelle' => 'IEF Test',
            'ia_id' => $this->iaId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $establishmentId = DB::table('etablissements')->insertGetId([
            'code' => 'ETAB-TEST',
            'libelle' => 'Établissement Test',
            'ief_id' => $this->iefId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->teacher = Enseignant::query()->create([
            'matricule' => 'TEST-001',
            'salaire_base' => 450000,
            'nombre_parts' => 2,
            'actif' => true,
            'etablissement_id' => $establishmentId,
        ]);
        User::query()->create([
            'nom' => 'DIOP',
            'prenom' => 'Mamadou',
            'email' => 'mamadou.diop@sicore.sn',
            'password' => 'secret123',
            'role_id' => $role->id,
            'enseignant_id' => $this->teacher->id,
        ]);

        Sanctum::actingAs($this->admin, [
            'payroll:read',
            'payroll:write',
            'payroll:close',
        ]);
    }

    public function test_page_de_paie_est_alimentee_par_api(): void
    {
        $period = $this->period();
        $this->seed(GestionPaieSeeder::class);
        PayrollAttendance::query()->create([
            'payroll_period_id' => $period->id,
            'enseignant_id' => $this->teacher->id,
            'absence_days' => 1,
            'delay_minutes' => 15,
            'deduction_amount' => 15000,
            'status' => 'draft',
        ]);

        $this->getJson('/api/payroll/pages/paie-etats-presence?period_id='.$period->id)
            ->assertOk()
            ->assertJsonPath('data.period.code', '2026-07')
            ->assertJsonPath('data.teaching_corps.0.code', 'VAC')
            ->assertJsonPath('data.teaching_corps.1.code', 'PC')
            ->assertJsonPath('data.academic_years.0.label', '2025-2026')
            ->assertJsonCount(12, 'data.payroll_months')
            ->assertJsonPath('data.academic_inspections.0.label', 'IA Test')
            ->assertJsonPath('data.education_inspections.0.ia_id', $this->iaId)
            ->assertJsonPath('data.teachers.0.ief_id', $this->iefId)
            ->assertJsonPath('data.teachers.0.value', $this->teacher->id)
            ->assertJsonPath('data.supports_hierarchy_filter', true)
            ->assertJsonPath('data.row_filters.0.ia_id', $this->iaId)
            ->assertJsonPath('data.row_filters.0.ief_id', $this->iefId)
            ->assertJsonPath('data.row_filters.0.matricule', 'TEST-001')
            ->assertJsonPath('data.rows.0.1', 'TEST-001')
            ->assertJsonPath('data.rows.0.4', '15 000 FCFA');
    }

    public function test_recherche_hierarchique_reste_disponible_quand_la_periode_na_pas_encore_de_bulletin(): void
    {
        $period = $this->period();

        $this->getJson('/api/payroll/pages/paie-bulletins?period_id='.$period->id)
            ->assertOk()
            ->assertJsonPath('data.period.code', '2026-07')
            ->assertJsonPath('data.supports_hierarchy_filter', true)
            ->assertJsonCount(0, 'data.rows')
            ->assertJsonCount(0, 'data.row_filters');
    }

    public function test_cycle_complet_calcul_validation_cloture_et_verrouillage(): void
    {
        $period = $this->period();
        PayrollAttendance::query()->create([
            'payroll_period_id' => $period->id,
            'enseignant_id' => $this->teacher->id,
            'absence_days' => 1,
            'delay_minutes' => 0,
            'deduction_amount' => 15000,
            'status' => 'draft',
        ]);
        PayrollElement::query()->create([
            'payroll_period_id' => $period->id,
            'enseignant_id' => $this->teacher->id,
            'code' => 'PRIME_TEST',
            'label' => 'Prime de test',
            'category' => 'earning',
            'source' => 'manual',
            'amount' => 50000,
            'status' => 'draft',
            'created_by' => $this->admin->id,
        ]);

        $this->postAction('calculate-payroll', [
            'payroll_period_id' => $period->id,
        ], 'calc-before-validation')->assertStatus(409);

        $this->postAction('validate-attendance', [
            'payroll_period_id' => $period->id,
        ], 'validate-attendance')->assertOk();
        $this->postAction('validate-elements', [
            'payroll_period_id' => $period->id,
        ], 'validate-elements')->assertOk();
        $calculation = $this->postAction('calculate-payroll', [
            'payroll_period_id' => $period->id,
        ], 'calculate')->assertOk()->assertJsonPath('data.employee_count', 1);
        $this->getJson('/api/payroll/payslips/'.$calculation->json('data.payslips.0.id'))
            ->assertOk()
            ->assertJsonPath('data.teacher.matricule', 'TEST-001')
            ->assertJsonCount(6, 'data.lines');
        $this->postAction('validate-payroll', [
            'payroll_period_id' => $period->id,
        ], 'validate-payroll')->assertOk();

        $period->refresh();
        $this->postAction('close-period', [
            'payroll_period_id' => $period->id,
            'confirmation' => $period->code,
            'expected_version' => $period->version,
        ], 'close-period')->assertOk()->assertJsonPath('data.status', 'closed');

        $this->postAction('add-element', [
            'ia_id' => $this->iaId,
            'ief_id' => $this->iefId,
            'matricule' => $this->teacher->matricule,
            'payroll_period_id' => $period->id,
            'enseignant_id' => $this->teacher->id,
            'code' => 'RETENUE_APRES_CLOTURE',
            'label' => 'Retenue interdite',
            'category' => 'deduction',
            'amount' => 1000,
        ], 'write-after-close')->assertStatus(409);
    }

    public function test_idempotence_empeche_un_double_traitement(): void
    {
        $period = $this->period();
        $payload = [
            'ia_id' => $this->iaId,
            'ief_id' => $this->iefId,
            'matricule' => $this->teacher->matricule,
            'payroll_period_id' => $period->id,
            'enseignant_id' => $this->teacher->id,
            'absence_days' => 0,
            'delay_minutes' => 0,
        ];

        $this->postAction('save-attendance', $payload, 'attendance-once')->assertOk();
        $this->postAction('save-attendance', $payload, 'attendance-once')
            ->assertOk()
            ->assertJsonPath('replayed', true);

        $this->assertDatabaseCount('payroll_attendances', 1);
        $this->assertDatabaseCount('payroll_audit_logs', 1);
    }

    public function test_matricule_doit_appartenir_a_la_hierarchie_selectionnee(): void
    {
        $otherIa = DB::table('ias')->insertGetId([
            'code' => 'IA-AUTRE',
            'libelle' => 'IA Autre',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $otherIef = DB::table('iefs')->insertGetId([
            'code' => 'IEF-AUTRE',
            'libelle' => 'IEF Autre',
            'ia_id' => $otherIa,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->postAction('save-attendance', [
            'ia_id' => $otherIa,
            'ief_id' => $otherIef,
            'matricule' => $this->teacher->matricule,
            'payroll_period_id' => $this->period()->id,
            'enseignant_id' => $this->teacher->id,
            'absence_days' => 0,
            'delay_minutes' => 0,
        ], 'wrong-hierarchy')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('enseignant_id');

        $this->assertDatabaseCount('payroll_attendances', 0);
    }

    public function test_ajout_element_variable_utilise_le_matricule_filtre_par_ia_et_ief(): void
    {
        $period = $this->period();

        $this->postAction('add-element', [
            'ia_id' => $this->iaId,
            'ief_id' => $this->iefId,
            'matricule' => $this->teacher->matricule,
            'payroll_period_id' => $period->id,
            'enseignant_id' => $this->teacher->id,
            'code' => 'PRIME_HIERARCHIE',
            'label' => 'Prime hiérarchique',
            'category' => 'earning',
            'amount' => 25000,
        ], 'element-hierarchy')
            ->assertOk()
            ->assertJsonPath('data.enseignant_id', $this->teacher->id);

        $this->assertDatabaseHas('payroll_elements', [
            'payroll_period_id' => $period->id,
            'enseignant_id' => $this->teacher->id,
            'code' => 'PRIME_HIERARCHIE',
            'amount' => 25000,
        ]);
    }

    public function test_matricule_saisi_doit_correspondre_au_formateur_resolu(): void
    {
        $this->postAction('save-attendance', [
            'ia_id' => $this->iaId,
            'ief_id' => $this->iefId,
            'matricule' => 'TEST-999',
            'payroll_period_id' => $this->period()->id,
            'enseignant_id' => $this->teacher->id,
            'absence_days' => 0,
            'delay_minutes' => 0,
        ], 'wrong-matricule')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('enseignant_id');

        $this->assertDatabaseCount('payroll_attendances', 0);
    }

    public function test_bulletin_contractuel_reproduit_le_cas_de_reference_recu(): void
    {
        $period = $this->period();
        $this->postAction('configure-teacher-payroll', [
            ...$this->teacherHierarchyPayload(),
            'type_engagement' => 'contractuel',
            'payroll_diploma_level' => 'BAC_BT',
            'payroll_category_level' => 1,
            'impr_monthly_amount' => 11767,
            'trimf_monthly_amount' => 500,
            'ipm_monthly_amount' => 4500,
            'union_checkoff_monthly_amount' => 1000,
        ], 'configure-contractuel')->assertOk();

        $this->teacher->refresh();
        $this->assertSame('152773.00', $this->teacher->salaire_base);
        $this->assertSame('BAC_BT', $this->teacher->payroll_diploma_level);

        $this->postAction('add-element', [
            ...$this->teacherHierarchyPayload(),
            'payroll_period_id' => $period->id,
            'code' => 'TABASKI_RETENUE',
            'label' => 'Retenue Tabaski',
            'category' => 'deduction',
            'amount' => 10000,
        ], 'contractuel-tabaski')->assertOk();
        $this->postAction('validate-elements', [
            'payroll_period_id' => $period->id,
        ], 'validate-contractuel-elements')->assertOk();
        $this->postAction('calculate-payroll', [
            'payroll_period_id' => $period->id,
        ], 'calculate-contractuel')->assertOk();

        $payslip = PayrollPayslip::query()
            ->where('payroll_period_id', $period->id)
            ->where('enseignant_id', $this->teacher->id)
            ->firstOrFail();

        $this->assertSame('302773.00', $payslip->gross_amount);
        $this->assertSame('42103.00', $payslip->deduction_amount);
        $this->assertSame('21504.00', $payslip->employer_contribution_amount);
        $this->assertSame('260670.00', $payslip->net_amount);
        $this->assertSame('contractuel', $payslip->profile_snapshot['engagement_type']);
        $this->assertEquals(70000, $payslip->profile_snapshot['ird']);
        $this->assertDatabaseHas('payroll_payslip_lines', [
            'payroll_payslip_id' => $payslip->id,
            'code' => 'IPRES_SALARIE',
            'amount' => 14336,
        ]);
        $this->assertDatabaseHas('payroll_payslip_lines', [
            'payroll_payslip_id' => $payslip->id,
            'code' => 'IRD',
            'amount' => 70000,
        ]);
        $this->assertDatabaseHas('payroll_payslip_lines', [
            'payroll_payslip_id' => $payslip->id,
            'code' => 'SALAIRE_BASE',
            'amount' => 97773,
        ]);
        $this->assertSame(8, $payslip->lines()->where('source', 'salary_increase')->count());
        $this->assertSame(
            '55000.00',
            number_format(
                (float) $payslip->lines()->where('source', 'salary_increase')->sum('amount'),
                2,
                '.',
                ''
            )
        );
        $this->assertDatabaseHas('payroll_payslip_lines', [
            'payroll_payslip_id' => $payslip->id,
            'code' => 'AUG_JAN_2019',
            'label' => 'Augmentation janvier 2019',
            'amount' => 5000,
        ]);
        $this->getJson('/api/payroll/payslips/'.$payslip->id)
            ->assertOk()
            ->assertJsonPath('data.lines.1.is_augmentation', true)
            ->assertJsonPath('data.profile.engagement_label', 'Professeur contractuel')
            ->assertJsonPath('data.profile.diploma', 'BAC / BT')
            ->assertJsonPath('data.profile.category', 1)
            ->assertJsonPath('data.teacher.academic_inspection', 'IA Test')
            ->assertJsonPath('data.teacher.education_inspection', 'IEF Test')
            ->assertJsonPath('data.teacher.establishment', 'Établissement Test');
        $this->getJson('/api/payroll/pages/paie-cotisations-sociales?period_id='.$period->id)
            ->assertOk()
            ->assertJsonPath('data.rows.0.1', '1')
            ->assertJsonPath('data.rows.0.2', '14 336 FCFA')
            ->assertJsonPath('data.rows.0.3', '21 504 FCFA')
            ->assertJsonPath('data.rows.0.4', '35 840 FCFA');
    }

    public function test_bulletin_vacataire_reproduit_le_bulletin_de_mars_2026_recu(): void
    {
        $period = $this->period();
        $this->postAction('configure-teacher-payroll', [
            ...$this->teacherHierarchyPayload(),
            'type_engagement' => 'vacataire',
            'impr_monthly_amount' => 10500,
            'trimf_monthly_amount' => 400,
        ], 'configure-vacataire')->assertOk();

        $this->postAction('add-element', [
            ...$this->teacherHierarchyPayload(),
            'payroll_period_id' => $period->id,
            'code' => 'TABASKI_RETENUE',
            'label' => 'Retenue Tabaski',
            'category' => 'deduction',
            'amount' => 10000,
        ], 'vacataire-tabaski')->assertOk();
        $this->postAction('validate-elements', [
            'payroll_period_id' => $period->id,
        ], 'validate-vacataire-elements')->assertOk();
        $this->postAction('calculate-payroll', [
            'payroll_period_id' => $period->id,
        ], 'calculate-vacataire')->assertOk();

        $payslip = PayrollPayslip::query()
            ->where('payroll_period_id', $period->id)
            ->where('enseignant_id', $this->teacher->id)
            ->firstOrFail();
        $codes = $payslip->lines()->pluck('code')->all();

        $this->assertSame('150000.00', $payslip->gross_amount);
        $this->assertSame('20900.00', $payslip->deduction_amount);
        $this->assertSame('0.00', $payslip->employer_contribution_amount);
        $this->assertSame('129100.00', $payslip->net_amount);
        $this->assertEqualsCanonicalizing(
            ['SALAIRE_BASE', 'TABASKI_RETENUE', 'IMPR', 'TRIMF'],
            $codes
        );
        $this->assertNotContains('IRD', $codes);
        $this->assertNotContains('IPRES_SALARIE', $codes);
    }

    public function test_tabaski_collective_cible_corps_plusieurs_ia_et_mois_sans_matricule(): void
    {
        $categoryId = DB::table('categories')->insertGetId([
            'code' => 'TEST-PAIE',
            'libelle' => 'Paie test',
            'ordre' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $corpsId = DB::table('corps_enseignant')->insertGetId([
            'code' => 'PC',
            'libelle' => 'Professeurs contractuels',
            'categorie_id' => $categoryId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $academicYearId = DB::table('annee_academiques')->insertGetId([
            'libelle' => '2025-2026',
            'date_debut' => '2025-10-01',
            'date_fin' => '2026-09-30',
            'en cours' => true,
            'est_cloturee' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $period = $this->period();
        foreach (['2025-10', '2025-11', '2025-12', '2026-01', '2026-02', '2026-03', '2026-04', '2026-05', '2026-06'] as $code) {
            PayrollPeriod::query()->create([
                'code' => $code,
                'label' => $code,
                'start_date' => $code.'-01',
                'end_date' => CarbonImmutable::parse($code.'-01')->endOfMonth()->toDateString(),
                'status' => PayrollPeriod::STATUS_OPEN,
            ]);
        }

        $this->teacher->update([
            'corps_id' => $corpsId,
            'ia_id' => $this->iaId,
            'ief_id' => $this->iefId,
            'type_engagement' => 'contractuel',
            'payroll_profile_configured_at' => now(),
        ]);
        $second = Enseignant::query()->create([
            'matricule' => 'TEST-002',
            'nom' => 'FALL',
            'prenom' => 'Awa',
            'corps_id' => $corpsId,
            'ia_id' => $this->iaId,
            'ief_id' => $this->iefId,
            'type_engagement' => 'contractuel',
            'salaire_base' => 152773,
            'nombre_parts' => 1,
            'actif' => true,
            'est_actif' => true,
            'etablissement_id' => $this->teacher->etablissement_id,
            'payroll_profile_configured_at' => now(),
        ]);
        User::query()->create([
            'nom' => 'FALL',
            'prenom' => 'Awa',
            'email' => 'awa.fall@sicore.sn',
            'password' => 'secret123',
            'role_id' => $this->admin->role_id,
            'enseignant_id' => $second->id,
        ]);

        $basePayload = [
            'corps_id' => $corpsId,
            'ia_ids' => [$this->iaId],
            'annee_academique_id' => $academicYearId,
            'amount' => 100000,
        ];
        $this->postAction('apply-tabaski-advance', [...$basePayload, 'month' => 7], 'tabaski-advance')
            ->assertOk()
            ->assertJsonPath('data.affected_teachers', 2)
            ->assertJsonPath('data.created_elements', 2)
            ->assertJsonPath('data.academic_year', '2025-2026');

        $this->assertDatabaseHas('payroll_elements', [
            'payroll_period_id' => $period->id,
            'enseignant_id' => $this->teacher->id,
            'code' => 'TABASKI_AVANCE',
            'amount' => 100000,
            'annee_academique_id' => $academicYearId,
            'application_corps_id' => $corpsId,
            'application_ia_id' => $this->iaId,
            'status' => 'validated',
        ]);

        $this->postAction('apply-tabaski-deduction', [
            ...$basePayload,
            'months' => [10, 11, 12, 1, 2, 3, 4, 5, 6],
        ], 'tabaski-nine-months')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('months');

        $this->postAction('apply-tabaski-deduction', [
            ...$basePayload,
            'months' => [10, 11, 12, 1, 2, 3, 4, 5, 6, 7],
        ], 'tabaski-ten-months')
            ->assertOk()
            ->assertJsonPath('data.created_elements', 20)
            ->assertJsonCount(10, 'data.payroll_period_ids');

        $this->assertSame(20, PayrollElement::query()->where('code', 'TABASKI_RETENUE')->count());
        $this->assertSame(1, PayrollElement::query()
            ->where('code', 'TABASKI_RETENUE')
            ->distinct('application_reference')
            ->count('application_reference'));
    }

    public function test_gestion_paie_seeder_est_idempotent(): void
    {
        $this->seed(GestionPaieSeeder::class);
        $this->seed(GestionPaieSeeder::class);

        $this->assertSame(2, DB::table('corps_enseignant')->whereIn('code', ['VAC', 'PC'])->count());
        $this->assertSame(1, DB::table('annee_academiques')->where('libelle', '2025-2026')->count());
        $this->assertSame(12, PayrollPeriod::query()
            ->whereBetween('code', ['2025-10', '2026-09'])
            ->count());
    }

    public function test_les_codes_calcules_ne_peuvent_pas_etre_saisis_manuellement(): void
    {
        $this->postAction('add-element', [
            ...$this->teacherHierarchyPayload(),
            'payroll_period_id' => $this->period()->id,
            'code' => 'IMPR',
            'label' => 'IMPR manuelle',
            'category' => 'deduction',
            'amount' => 1000,
        ], 'reserved-code')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('code');

        $this->assertDatabaseCount('payroll_elements', 0);
    }

    public function test_un_profil_pc_ou_vacataire_incomplet_bloque_le_calcul_global(): void
    {
        $this->teacher->update([
            'type_engagement' => 'contractuel',
            'salaire_base' => 0,
        ]);

        $this->postAction('calculate-payroll', [
            'payroll_period_id' => $this->period()->id,
        ], 'incomplete-profile')
            ->assertStatus(409)
            ->assertJsonPath(
                'message',
                '1 profil(s) contractuel(s) ou vacataire(s) sont incomplets. Corrigez-les dans « Paie non générée » avant le calcul.'
            );

        $this->assertDatabaseCount('payroll_runs', 0);
    }

    private function period(): PayrollPeriod
    {
        return PayrollPeriod::query()->create([
            'code' => '2026-07',
            'label' => 'Juillet 2026',
            'start_date' => '2026-07-01',
            'end_date' => '2026-07-31',
            'status' => PayrollPeriod::STATUS_OPEN,
        ]);
    }

    /** @return array<string, int|string> */
    private function teacherHierarchyPayload(): array
    {
        return [
            'ia_id' => $this->iaId,
            'ief_id' => $this->iefId,
            'matricule' => $this->teacher->matricule,
            'enseignant_id' => $this->teacher->id,
        ];
    }

    private function postAction(string $action, array $payload, string $key)
    {
        return $this->postJson(
            '/api/payroll/actions/'.$action,
            $payload,
            ['Idempotency-Key' => $key]
        );
    }
}
