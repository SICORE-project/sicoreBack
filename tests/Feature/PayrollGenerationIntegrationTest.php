<?php

namespace Tests\Feature;

use App\Models\Admin\User;
use App\Models\Enseignant;
use App\Models\PayrollPayslip;
use App\Models\PayrollPeriod;
use App\Services\PayrollGenerationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Tests\TestCase;

class PayrollGenerationIntegrationTest extends TestCase
{
    use RefreshDatabase { refreshDatabase as private refreshPayrollDatabase; }

    public function refreshDatabase(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') { $this->markTestSkipped('Les migrations du projet nécessitent MySQL.'); }
        $this->refreshPayrollDatabase();
    }

    private User $actor;
    private int $year;
    private int $ia;
    private int $otherIa;
    private int $ief;
    private array $teacherIds;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actor = User::findOrFail(DB::table('users')->insertGetId([
            'nom' => 'Test', 'prenom' => 'Paie', 'email' => 'generation@example.test', 'password' => 'unused',
        ]));
        $this->ia = DB::table('ias')->insertGetId(['code' => 'GEN-A', 'libelle' => 'IA A']);
        $this->otherIa = DB::table('ias')->insertGetId(['code' => 'GEN-B', 'libelle' => 'IA B']);
        $this->ief = DB::table('iefs')->insertGetId(['code' => 'GEN-IEF-A', 'libelle' => 'IEF A', 'ia_id' => $this->ia]);
        $otherIef = DB::table('iefs')->insertGetId(['code' => 'GEN-IEF-B', 'libelle' => 'IEF B', 'ia_id' => $this->otherIa]);
        $this->year = DB::table('annee_academiques')->insertGetId([
            'libelle' => '2026-2027', 'date_debut' => '2026-10-01', 'date_fin' => '2027-09-30', 'est_active' => true,
        ]);
        DB::table('periode_de_paies')->insert(['code' => '10', 'libelle' => 'OCTOBRE']);
        $category = DB::table('categories')->insertGetId(['libelle' => 'Contractuelle', 'ordre' => 0]);
        $diploma = DB::table('diplomes')->insertGetId(['libelle' => 'LICENCE', 'categorie_id' => $category, 'salaire_brut' => 145000]);
        $pc = DB::table('statuts_enseignant')->insertGetId(['code' => 'PC', 'libelle' => 'Professeurs contractuels']);
        $vac = DB::table('statuts_enseignant')->insertGetId(['code' => 'VAC', 'libelle' => 'Vacataires']);
        $this->teacherIds = [];
        foreach ([$pc, $vac, $pc] as $index => $status) {
            $this->teacherIds[] = DB::table('enseignants')->insertGetId([
                'matricule' => 'GEN-'.($index + 1), 'nom' => 'Personnel', 'prenom' => 'Agent '.($index + 1),
                'statut_enseignant_id' => $status, 'diplome_id' => $diploma,
                'ia_id' => $index === 2 ? $this->otherIa : $this->ia,
                'ief_id' => $index === 2 ? $otherIef : $this->ief, 'est_actif' => true,
            ]);
        }
        foreach (['IMPR' => 12500, 'TRIMF' => 500, 'IPM' => 4500, 'CHECKOFF_UES' => 1000] as $code => $amount) {
            DB::table('rubrique_paies')->insert([
                'code' => $code, 'libelle' => $code, 'type' => 'retenue', 'periodicite' => 'mensuelle', 'montant' => $amount,
            ]);
        }
        foreach (config('payroll_reference.contract_salary_increases') as $increase) {
            DB::table('rubrique_paies')->insert(['code' => $increase['code'], 'libelle' => $increase['label'],
                'type' => 'gain', 'periodicite' => 'mensuelle', 'montant' => $increase['amount']]);
        }
    }

    private function generate(array $selection)
    {
        return app(PayrollGenerationService::class)->generate([
            'annee_academique_id' => $this->year, 'month' => 10, ...$selection,
        ], $this->actor, Request::create('/generation', 'POST'), null);
    }

    public function test_individual_generation_reads_personnel_without_saving_a_second_profile(): void
    {
        $teacher = Enseignant::findOrFail($this->teacherIds[0]);
        $before = $teacher->toArray();
        $run = $this->generate(['generation_scope' => 'teacher', 'enseignant_id' => $teacher->id]);
        $this->assertSame(1, $run->employee_count);
        $slip = $run->payslips->first();
        $this->assertSame('145000.00', number_format((float) $slip->profile_snapshot['base_salary'], 2, '.', ''));
        $this->assertSame('LICENCE', $slip->profile_snapshot['diploma_label']);
        $this->assertSame('Contractuelle', $slip->profile_snapshot['category_label']);
        $this->assertSame('12500.00', $slip->lines->firstWhere('code', 'IMPR')->amount);
        $this->assertSame('145000.00', $slip->lines->firstWhere('code', 'SALAIRE_BASE')->amount);
        $this->assertSame('diploma_salary', $slip->lines->firstWhere('code', 'SALAIRE_BASE')->source);
        $this->assertSame(8, $slip->lines->where('source', 'salary_increase')->count());
        $this->assertSame(55000.0, (float) $slip->lines->where('source', 'salary_increase')->sum('amount'));
        $this->assertSame(360000.0, (float) $slip->gross_amount);
        DB::table('rubrique_paies')->insert([
            'code' => 'SALAIRE_BASE', 'libelle' => 'Ancien salaire configuré', 'type' => 'gain',
            'periodicite' => 'mensuelle', 'montant' => 999999, 'est_afficher_bulletin' => false,
        ]);
        $display = app(\App\Services\PayrollPayslipPresentationService::class)->present($slip);
        $this->assertSame('SALAIRE BRUT', $display['lines'][0]['label']);
        $this->assertSame('145000.00', $display['lines'][0]['amount']);
        $this->assertSame((float) $slip->gross_amount, (float) $slip->lines->where('category', 'earning')->sum('amount'));
        $this->assertSame($before, $teacher->fresh()->toArray());
        $this->assertNull($teacher->fresh()->payroll_profile_configured_at);
    }

    public function test_full_ia_and_individual_recalculation_preserve_other_teachers_and_aggregate_totals(): void
    {
        $first = $this->generate(['generation_scope' => 'teacher', 'enseignant_id' => $this->teacherIds[2]]);
        $outside = $first->payslips->first()->toArray();
        $run = $this->generate(['generation_scope' => 'ia', 'ia_id' => $this->ia]);
        $this->assertCount(3, $run->payslips);
        $this->assertSame(3, $run->employee_count);
        $this->assertSame($outside, PayrollPayslip::findOrFail($outside['id'])->load('lines')->toArray());
        $vac = $run->payslips->firstWhere('enseignant_id', $this->teacherIds[1]);
        $this->assertNull($vac->lines->firstWhere('code', 'IPM'));
        $vacId = $vac->id;
        $this->generate(['generation_scope' => 'teacher', 'enseignant_id' => $this->teacherIds[0]]);
        $this->assertTrue(PayrollPayslip::where('id', $vacId)->exists());
        $this->assertTrue(PayrollPayslip::where('id', $outside['id'])->exists());
        $period = PayrollPeriod::first();
        $this->assertSame((float) PayrollPayslip::sum('net_amount'), (float) $period->total_net);
        $this->assertSame(3, $period->employee_count);
    }

    public function test_incomplete_group_is_atomic_and_does_not_guess_a_diploma(): void
    {
        DB::table('enseignants')->where('id', $this->teacherIds[1])->update(['diplome_id' => null]);
        try {
            $this->generate(['generation_scope' => 'ia', 'ia_id' => $this->ia]);
            $this->fail('Dossier incomplet accepté');
        } catch (ValidationException $exception) {
            $this->assertStringContainsString('GEN-2', $exception->getMessage());
            $this->assertSame(0, PayrollPayslip::count());
            $this->assertSame(0, DB::table('payroll_runs')->count());
        }
    }

    public function test_missing_rubric_amount_blocks_generation_without_changing_personnel(): void
    {
        DB::table('rubrique_paies')->where('code', 'IMPR')->update(['montant' => null]);
        try {
            $this->generate(['generation_scope' => 'teacher', 'enseignant_id' => $this->teacherIds[0]]);
            $this->fail('Montant vide accepté');
        } catch (ValidationException $exception) {
            $this->assertStringContainsString('IMPR', $exception->getMessage());
            $this->assertSame(0, PayrollPayslip::count());
            $this->assertNull(Enseignant::find($this->teacherIds[0])->payroll_profile_configured_at);
        }
    }

    public function test_validated_period_is_not_regenerated(): void
    {
        $this->generate(['generation_scope' => 'teacher', 'enseignant_id' => $this->teacherIds[0]]);
        PayrollPeriod::first()->update(['status' => 'validated']);
        $this->expectException(ConflictHttpException::class);
        $this->generate(['generation_scope' => 'teacher', 'enseignant_id' => $this->teacherIds[1]]);
    }

    public function test_ief_outside_the_selected_ia_does_not_generate_any_teacher(): void
    {
        $this->expectException(ValidationException::class);
        $this->generate(['generation_scope' => 'ia', 'ia_id' => $this->otherIa, 'ief_id' => $this->ief]);
    }
}
