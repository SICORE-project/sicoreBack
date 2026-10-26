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
