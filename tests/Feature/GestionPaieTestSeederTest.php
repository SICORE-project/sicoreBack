<?php

namespace Tests\Feature;

use App\Models\PayrollPayslip;
use App\Models\PayrollPeriod;
use App\Services\PayrollPageService;
use Database\Seeders\GestionPaieSeeder;
use Database\Seeders\GestionPaieTestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GestionPaieTestSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_les_bulletins_de_recette_detaillent_toutes_les_lignes_de_tracabilite(): void
    {
        $this->seed(GestionPaieSeeder::class);
        $this->seed(GestionPaieTestSeeder::class);
        $this->seed(GestionPaieTestSeeder::class);

        $vacataire = PayrollPayslip::query()
            ->whereHas('enseignant', fn ($query) => $query->where('matricule', 'VAC-TEST-001'))
            ->firstOrFail();
        $contractuel = PayrollPayslip::query()
            ->whereHas('enseignant', fn ($query) => $query->where('matricule', 'PC-TEST-001'))
            ->firstOrFail();

        $this->assertSame('150000.00', $vacataire->gross_amount);
        $this->assertSame('20900.00', $vacataire->deduction_amount);
        $this->assertSame('129100.00', $vacataire->net_amount);
        $this->assertSame(4, $vacataire->lines()->count());
        $this->assertTrue($vacataire->lines()->where('code', 'TABASKI_RETENUE')->exists());

        $this->assertSame('302773.00', $contractuel->gross_amount);
        $this->assertSame('42103.00', $contractuel->deduction_amount);
        $this->assertSame('21504.00', $contractuel->employer_contribution_amount);
        $this->assertSame('260670.00', $contractuel->net_amount);
        $this->assertSame(19, $contractuel->lines()->count());
        $this->assertSame(8, $contractuel->lines()->where('source', 'salary_increase')->count());
        $this->assertSame('contractuel', $contractuel->profile_snapshot['engagement_type']);
    }

    public function test_les_donnees_de_recette_couvrent_plusieurs_structures_et_la_paie_non_generee(): void
    {
        $this->seed(GestionPaieSeeder::class);
        $this->seed(GestionPaieTestSeeder::class);
        $this->seed(GestionPaieTestSeeder::class);

        $period = PayrollPeriod::query()
            ->where('status', PayrollPeriod::STATUS_OPEN)
            ->latest('start_date')
            ->firstOrFail();

        $this->assertDatabaseHas('ias', ['code' => 'IA-KLK']);
        $this->assertDatabaseHas('ias', ['code' => 'IA-ZIG']);
        $this->assertDatabaseHas('ias', ['code' => 'IA-DBL']);
        $this->assertDatabaseHas('iefs', ['code' => 'IEF-DBL-MBK']);
        $this->assertDatabaseHas('enseignants', [
            'matricule' => 'PC-TEST-009',
            'payroll_profile_configured_at' => null,
        ]);
        $this->assertSame(6, PayrollPayslip::query()
            ->where('payroll_period_id', $period->id)
            ->whereHas('enseignant', fn ($query) => $query->where('matricule', 'like', '%-TEST-%'))
            ->count());

        $pages = app(PayrollPageService::class);
        $notGenerated = $pages->page('paie-non-generee', $period->id);
        $payslips = $pages->page('paie-bulletins', $period->id);

        $this->assertCount(12, $notGenerated['rows']);
        $this->assertContains('Calcul non exécuté', collect($notGenerated['rows'])->pluck(7));
        $this->assertContains('Type d’engagement absent', collect($notGenerated['rows'])->pluck(7));
        $this->assertSame('Mois du bulletin', $payslips['filters'][0]['label']);
        $this->assertSame('Mois du bulletin', $payslips['columns'][1]);
        $this->assertSame($period->label, $payslips['rows'][0][1]);
    }
}
