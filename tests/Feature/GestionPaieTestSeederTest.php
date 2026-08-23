<?php

namespace Tests\Feature;

use App\Models\PayrollPayslip;
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
}
