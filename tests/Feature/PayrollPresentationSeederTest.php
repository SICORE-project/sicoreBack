<?php

namespace Tests\Feature;

use App\Models\Enseignant;
use App\Models\PayrollElement;
use App\Models\PayrollPayslip;
use App\Models\PayrollPeriod;
use App\Models\User;
use App\Services\PayrollPageService;
use Database\Seeders\PayrollPresentationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PayrollPresentationSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_donnees_de_recette_fournissent_des_bulletins_et_un_cycle_complet(): void
    {
        $this->seed(PayrollPresentationSeeder::class);

        $admin = User::query()->where('email', 'admin@sicore.sn')->firstOrFail();
        Sanctum::actingAs($admin, ['payroll:read', 'payroll:write', 'payroll:close']);

        $march = PayrollPeriod::query()->where('code', '2026-03')->firstOrFail();
        $period = PayrollPeriod::query()->where('code', '2026-04')->firstOrFail();
        $vacataire = $this->teacher('VAC-2026-001');
        $contractuel = $this->teacher('PC-2026-001');

        $this->assertDatabaseCount('enseignants', 20);
        $this->assertDatabaseCount('payroll_periods', 2);
        $this->assertDatabaseCount('payroll_attendances', 40);
        $this->assertDatabaseCount('payroll_elements', 100);
        $this->assertSame(PayrollPeriod::STATUS_VALIDATED, $march->status);
        $this->assertSame(PayrollPeriod::STATUS_OPEN, $period->status);
        $this->assertPayslip($march, $vacataire, 150000, 20900, 0, 129100);
        $this->assertPayslip($march, $contractuel, 302773, 42103, 21504, 260670);
        $contractReferencePayslip = PayrollPayslip::query()
            ->where('payroll_period_id', $march->id)
            ->where('enseignant_id', $contractuel->id)
            ->firstOrFail();
        $this->assertSame(
            8,
            $contractReferencePayslip->lines()->where('source', 'salary_increase')->count()
        );
        $this->assertDatabaseHas('payroll_payslip_lines', [
            'payroll_payslip_id' => $contractReferencePayslip->id,
            'code' => 'SALAIRE_BASE',
            'amount' => 97773,
        ]);
        $this->assertSame(
            60,
            PayrollElement::query()
                ->whereIn('code', ['TABASKI_AVANCE', 'TABASKI_RETENUE'])
                ->where('application_scope', 'collective')
                ->where('academic_year', '2025-2026')
                ->count()
        );
        $this->assertDatabaseHas('payroll_payslips', [
            'payroll_period_id' => $march->id,
            'enseignant_id' => $vacataire->id,
            'payment_status' => 'paid',
            'payment_reference' => 'VIR-202603-0001',
        ]);

        $this->getJson('/api/payroll/pages/paie-bulletins')
            ->assertOk()
            ->assertJsonPath('data.period.code', '2026-03')
            ->assertJsonPath('data.supports_hierarchy_filter', true)
            ->assertJsonCount(20, 'data.rows');

        foreach ([
            'paie-etats-presence',
            'paie-avance-tabaski',
            'paie-retenue-tabaski',
            'paie-retenues-rappel',
            'paie-exemptions',
            'paie-non-generee',
        ] as $slug) {
            $response = $this->getJson('/api/payroll/pages/'.$slug.'?period_id='.$period->id)
                ->assertOk()
                ->assertJsonCount(20, 'data.rows');
            if ($slug === 'paie-avance-tabaski') {
                $response
                    ->assertJsonPath('data.actions.0.code', 'apply-tabaski-advance')
                    ->assertJsonPath('data.columns.2', 'Corps d’enseignement')
                    ->assertJsonMissing(['Catégorie']);
            }
            if ($slug === 'paie-retenue-tabaski') {
                $response->assertJsonPath('data.actions.0.code', 'apply-tabaski-deduction');
            }
        }

        foreach ([
            'paie-etat-salaires',
            'paie-edition-salaires-banque',
            'paie-bulletins',
            'paie-sommes-percues',
        ] as $slug) {
            $this->getJson('/api/payroll/pages/'.$slug.'?period_id='.$march->id)
                ->assertOk()
                ->assertJsonCount(20, 'data.rows');
        }

        $this->postAction('calculate-payroll', [
            'payroll_period_id' => $period->id,
        ], 'acceptance-calculate-too-early')->assertStatus(409);

        $attendance = $period->attendances()->where('enseignant_id', $vacataire->id)->firstOrFail();
        $this->postAction('save-attendance', [
            ...$this->hierarchy($vacataire),
            'payroll_period_id' => $period->id,
            'absence_days' => 0,
            'delay_minutes' => 0,
            'notes' => 'Présence contrôlée pendant la recette.',
            'expected_version' => $attendance->version,
        ], 'acceptance-attendance')->assertOk();

        $this->postAction('add-element', [
            ...$this->hierarchy($vacataire),
            'payroll_period_id' => $period->id,
            'code' => 'RETENUE_RECETTE_TEST',
            'label' => 'Retenue temporaire de recette',
            'category' => 'deduction',
            'amount' => 1000,
        ], 'acceptance-reminder')->assertOk();

        $reminder = PayrollElement::query()->where('code', 'RETENUE_RECETTE_TEST')->firstOrFail();
        $this->postAction('exempt-element', [
            'payroll_element_id' => $reminder->id,
            'reason' => 'Retenue neutralisée pendant la recette fonctionnelle.',
            'expected_version' => $reminder->version,
        ], 'acceptance-exemption')->assertOk()->assertJsonPath('data.is_exempt', true);

        $this->postAction('validate-attendance', [
            'payroll_period_id' => $period->id,
        ], 'acceptance-validate-attendance')->assertOk();
        $this->postAction('validate-elements', [
            'payroll_period_id' => $period->id,
        ], 'acceptance-validate-elements')->assertOk();

        $calculation = $this->postAction('calculate-payroll', [
            'payroll_period_id' => $period->id,
        ], 'acceptance-calculate')
            ->assertOk()
            ->assertJsonPath('data.employee_count', 20);

        $this->assertNotNull($calculation->json('data.checksum'));
        $this->assertGreaterThan(0, (float) $calculation->json('data.total_gross'));
        $this->assertGreaterThan(0, (float) $calculation->json('data.total_net'));
        $this->assertPayslip($period, $vacataire, 155000, 21900, 0, 133100);
        $this->assertPayslip($period, $contractuel, 308773, 43353, 21504, 265420);

        $this->getJson('/api/payroll/pages/paie-cotisations-sociales?period_id='.$period->id)
            ->assertOk()
            ->assertJsonPath('data.rows.0.1', '10');

        $this->postAction('validate-payroll', [
            'payroll_period_id' => $period->id,
        ], 'acceptance-validate-payroll')->assertOk();

        foreach (Enseignant::query()->orderBy('id')->get() as $index => $teacher) {
            $payslip = PayrollPayslip::query()
                ->where('payroll_period_id', $period->id)
                ->where('enseignant_id', $teacher->id)
                ->firstOrFail();
            $this->postAction('mark-paid', [
                'payroll_payslip_id' => $payslip->id,
                'payment_reference' => 'VIR-202604-'.str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT),
                'expected_version' => $payslip->version,
            ], 'acceptance-payment-'.$teacher->id)
                ->assertOk()
                ->assertJsonPath('data.payment_status', 'paid');
        }

        $period->refresh();
        $this->postAction('close-period', [
            'payroll_period_id' => $period->id,
            'confirmation' => '2026-04',
            'expected_version' => $period->version,
        ], 'acceptance-close')->assertOk()->assertJsonPath('data.status', 'closed');

        $this->postAction('add-element', [
            ...$this->hierarchy($vacataire),
            'payroll_period_id' => $period->id,
            'code' => 'APRES_CLOTURE',
            'label' => 'Écriture interdite après clôture',
            'category' => 'deduction',
            'amount' => 1,
        ], 'acceptance-after-close')->assertStatus(409);

        foreach (PayrollPageService::SLUGS as $slug) {
            $this->getJson('/api/payroll/pages/'.$slug.'?period_id='.$period->id)->assertOk();
        }

        $this->get('/api/payroll/exports/paie-etat-salaires?period_id='.$period->id)
            ->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8');

        $this->assertDatabaseCount('payroll_payslips', 40);
        $this->assertDatabaseHas('payroll_audit_logs', ['action' => 'period.closed']);
        $this->assertDatabaseHas('payroll_audit_logs', ['action' => 'payslip.paid']);
        $this->assertDatabaseHas('payroll_audit_logs', ['action' => 'element.exempted']);
    }

    private function teacher(string $matricule): Enseignant
    {
        return Enseignant::query()
            ->with('etablissement.ief')
            ->where('matricule', $matricule)
            ->firstOrFail();
    }

    /** @return array<string, int|string> */
    private function hierarchy(Enseignant $teacher): array
    {
        return [
            'ia_id' => $teacher->etablissement->ief->ia_id,
            'ief_id' => $teacher->etablissement->ief_id,
            'matricule' => $teacher->matricule,
            'enseignant_id' => $teacher->id,
        ];
    }

    private function assertPayslip(
        PayrollPeriod $period,
        Enseignant $teacher,
        int $gross,
        int $deductions,
        int $employer,
        int $net
    ): void {
        $payslip = PayrollPayslip::query()
            ->where('payroll_period_id', $period->id)
            ->where('enseignant_id', $teacher->id)
            ->firstOrFail();

        $this->assertSame(number_format($gross, 2, '.', ''), $payslip->gross_amount);
        $this->assertSame(number_format($deductions, 2, '.', ''), $payslip->deduction_amount);
        $this->assertSame(number_format($employer, 2, '.', ''), $payslip->employer_contribution_amount);
        $this->assertSame(number_format($net, 2, '.', ''), $payslip->net_amount);
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
