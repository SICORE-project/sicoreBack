<?php

namespace Tests\Feature;

use App\Http\Controllers\PayrollPageController;
use App\Models\Admin\User;
use App\Models\PayrollPeriod;
use App\Services\PayrollCalculationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PayrollPayslipDisplayIntegrationTest extends TestCase
{
    use RefreshDatabase { refreshDatabase as private refreshPayrollDatabase; }

    public function test_pc_and_vac_deductions_use_catalogue_amounts_and_ignore_legacy_individual_values(): void
    {
        $actorId = DB::table('users')->insertGetId([
            'nom' => 'Test', 'prenom' => 'Rubriques', 'email' => 'rubrics@example.test', 'password' => 'unused',
        ]);
        foreach (['contractuel', 'vacataire'] as $type) {
            DB::table('enseignants')->insert([
                'matricule' => 'RUBRIC-'.$type, 'nom' => 'Test', 'prenom' => $type,
                'salaire_base' => 150000, 'est_actif' => true, 'type_engagement' => $type,
                'payroll_diploma_level' => $type === 'contractuel' ? 'BAC_BT' : null,
                'payroll_category_level' => $type === 'contractuel' ? 1 : null,
                'payroll_profile_configured_at' => now(),
                'impr_monthly_amount' => null, 'trimf_monthly_amount' => null,
                'ipm_monthly_amount' => 99999, 'union_checkoff_monthly_amount' => 99999,
            ]);
        }
        foreach (['IMPR' => 12500, 'TRIMF' => 500, 'IPM' => 4500, 'CHECKOFF_UES' => 1000] as $code => $amount) {
            DB::table('rubrique_paies')->insert([
                'code' => $code, 'libelle' => $code, 'type' => 'retenue', 'periodicite' => 'mensuelle',
                'montant' => $amount, 'est_actif' => true,
            ]);
        }
        $period = PayrollPeriod::create([
            'code' => '2026-10', 'label' => 'Octobre 2026', 'start_date' => '2026-10-01',
            'end_date' => '2026-10-31', 'status' => 'open',
        ]);
        $service = app(PayrollCalculationService::class);
        $run = $service->calculate($period, User::findOrFail($actorId));
        $this->assertCount(2, $run->payslips);
        foreach ($run->payslips as $payslip) {
            $this->assertSame('12500.00', $payslip->lines->firstWhere('code', 'IMPR')->amount);
            $this->assertSame('payroll_rubric', $payslip->lines->firstWhere('code', 'IMPR')->source);
            $this->assertSame(12500.0, (float) data_get($payslip->profile_snapshot, 'deduction_rubrics.impr.amount'));
            if ($payslip->profile_snapshot['engagement_type'] === 'contractuel') {
                $this->assertSame('4500.00', $payslip->lines->firstWhere('code', 'IPM')->amount);
                $this->assertSame('1000.00', $payslip->lines->firstWhere('code', 'CHECKOFF_UES')->amount);
            } else {
                $this->assertNull($payslip->lines->firstWhere('code', 'IPM'));
                $this->assertSame('137000.00', $payslip->net_amount);
            }
        }
        DB::table('rubrique_paies')->where('code', 'IMPR')->update(['montant' => 14000]);
        $this->assertSame('12500.00', $run->payslips->first()->fresh('lines')->lines->firstWhere('code', 'IMPR')->amount);
        $recalculated = $service->calculate($period, User::findOrFail($actorId));
        $this->assertSame('14000.00', $recalculated->payslips->first()->lines->firstWhere('code', 'IMPR')->amount);
    }

    public function refreshDatabase(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            $this->markTestSkipped('Les migrations complètes du projet nécessitent MySQL. Voir docs/bulletin-parametrage.md.');
        }
        $this->refreshPayrollDatabase();
    }

    public function test_calculation_persists_display_settings_and_api_preserves_financial_values(): void
    {
        $actorId = DB::table('users')->insertGetId([
            'nom' => 'Test', 'prenom' => 'Paie', 'email' => 'display-test@example.test',
            'password' => 'unused',
        ]);
        DB::table('enseignants')->insert([
            'matricule' => 'DISPLAY-001', 'nom' => 'Test', 'prenom' => 'Agent',
            'salaire_base' => 100000, 'est_actif' => true,
        ]);
        DB::table('rubrique_paies')->insert([
            'code' => 'SALAIRE_BASE', 'libelle' => 'Traitement paramétré', 'type' => 'gain',
            'periodicite' => 'mensuelle', 'montant' => 999999,
            'est_afficher_bulletin' => true,
        ]);
        $period = PayrollPeriod::create([
            'code' => '2026-10', 'label' => 'Octobre 2026',
            'start_date' => '2026-10-01', 'end_date' => '2026-10-31', 'status' => 'open',
        ]);
        $run = app(PayrollCalculationService::class)->calculate($period, User::findOrFail($actorId));
        $payslip = $run->payslips->first()->fresh();
        $this->assertSame('Traitement paramétré', data_get($payslip->profile_snapshot, 'bulletin_display.SALAIRE_BASE.label'));
        $this->assertSame('100000.00', $payslip->gross_amount);
        $newTeacherId = DB::table('enseignants')->insertGetId([
            'matricule' => 'NEW-REGISTERED', 'prenom' => 'Nouveau', 'nom' => 'Enseignant', 'est_actif' => true,
        ]);
        $teachers = \App\Models\Enseignant::whereIn('id', [$payslip->enseignant_id, $newTeacherId])->get();
        $report = (new \ReflectionMethod(\App\Services\PayrollPageService::class, 'payslipReport'))
            ->invoke(app(\App\Services\PayrollPageService::class), $period, $teachers);
        $this->assertCount(2, $report['rows']);
        $this->assertNotContains('Paiement', $report['columns']);
        $newRow = $report['rows']->first(fn ($row) => $row[2] === 'NEW-REGISTERED');
        $this->assertSame('Non calculé', $newRow[0]);
        $oldRow = $report['rows']->first(fn ($row) => $row[2] === 'DISPLAY-001');
        $this->assertSame('view-payslip', $oldRow[8]['actions'][0]['code']);
        $this->assertCount(1, $oldRow[8]['actions']);
        $this->assertSame(1, DB::table('payroll_payslips')->count());
        $financials = $payslip->only(['gross_amount', 'deduction_amount', 'net_amount']);

        $centreId = DB::table('centres_formation')->insertGetId(['code' => 'TEST-PAIE', 'nom' => 'Centre de test Paie']);
        $statusId = DB::table('statuts_enseignant')->insertGetId(['code' => 'PC', 'libelle' => 'Professeurs contractuels']);
        DB::table('enseignants')->where('id', $payslip->enseignant_id)->update([
            'centre_formation_id' => $centreId, 'statut_enseignant_id' => $statusId,
        ]);
        $bankId = DB::table('instituts_financieres')->insertGetId(['code' => 'TEST-BANK', 'libelle' => 'Banque de test']);
        DB::table('comptes_bancaires_enseignants')->insert([
            'enseignant_id' => $payslip->enseignant_id, 'institut_financier_id' => $bankId,
            'numero_compte' => 'DEMO-1234', 'est_principal' => true, 'est_actif' => true,
        ]);

        DB::table('rubrique_paies')->where('code', 'SALAIRE_BASE')->update([
            'libelle' => 'Libellé pour les calculs futurs', 'est_afficher_bulletin' => false,
        ]);
        $response = app(PayrollPageController::class)->payslip($payslip)->getData(true)['data'];
        $base = collect($response['lines'])->firstWhere('code', 'SALAIRE_BASE');
        $this->assertSame('Traitement paramétré', $base['label']);
        $this->assertSame('100000.00', $base['amount']);
        $this->assertSame('calculation_snapshot', $response['display_settings']['origin']);
        $this->assertSame('Centre de test Paie', $response['teacher']['establishment']);
        $this->assertSame('Banque de test', $response['teacher']['bank']);
        $this->assertSame('1234', $response['teacher']['account_last_four']);
        $this->assertSame('Professeurs contractuels', $response['teacher']['corps']);
        $this->assertSame($financials, $payslip->fresh()->only(array_keys($financials)));
    }
}
