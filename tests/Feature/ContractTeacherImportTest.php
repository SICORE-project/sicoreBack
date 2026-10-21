<?php

namespace Tests\Feature;

use App\Models\Enseignant;
use App\Models\TeacherImportBatch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ContractTeacherImportTest extends TestCase
{
    use RefreshDatabase;

    private string $csvPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->csvPath = storage_path(
            'framework/testing/pc-import-'.Str::lower(Str::random(8)).'.csv'
        );
        $stream = fopen($this->csvPath, 'wb');
        fwrite($stream, "\xEF\xBB\xBF");
        fputcsv($stream, [
            'ia',
            'ief',
            'matricule',
            'prenoms',
            'nom',
            'date_naissance',
            'lieu_naissance',
            'cni',
        ], ';');
        fputcsv($stream, [
            'IA TEST',
            'IEF TEST',
            '202600001/A',
            'Awa',
            'Diop',
            '1990-02-14',
            'Dakar',
            '1234567890123',
        ], ';');
        fputcsv($stream, [
            'IA TEST',
            'IEF TEST',
            '202600002/B',
            'Moussa',
            'Ndiaye',
            '1988-11-03',
            'Thiès',
            '9876543210123',
        ], ';');
        fclose($stream);
    }

    protected function tearDown(): void
    {
        if (isset($this->csvPath) && is_file($this->csvPath)) {
            unlink($this->csvPath);
        }

        parent::tearDown();
    }

    public function test_import_pc_est_complet_idempotent_et_sans_acces_login(): void
    {
        $this->artisan('sicore:import-pc', [
            'file' => $this->csvPath,
            '--period' => '2026-01',
        ])->assertSuccessful();

        $this->assertDatabaseCount('enseignants', 2);
        $this->assertDatabaseCount('teacher_import_batches', 1);
        $this->assertDatabaseHas('enseignants', [
            'matricule' => '202600001/A',
            'cni' => '1234567890123',
            'type_engagement' => 'contractuel',
            'source_reference' => '2026-01',
            'actif' => true,
            'salaire_base' => 0,
        ]);

        $teacher = Enseignant::query()
            ->with('etablissement.ief.ia')
            ->where('matricule', '202600001/A')
            ->firstOrFail();
        $this->assertSame('IEF TEST', $teacher->etablissement->ief->libelle);
        $this->assertSame('IA TEST', $teacher->etablissement->ief->ia->libelle);
        $this->assertSame('Affectation à préciser — IEF TEST', $teacher->etablissement->libelle);

        $account = User::query()->where('enseignant_id', $teacher->id)->firstOrFail();
        $this->assertFalse($account->login_enabled);
        $this->assertSame('Awa', $account->prenom);
        $this->assertSame('DIOP', $account->nom);

        $this->artisan('sicore:import-pc', [
            'file' => $this->csvPath,
            '--period' => '2026-01',
        ])->assertSuccessful();

        $this->assertDatabaseCount('enseignants', 2);
        $this->assertDatabaseCount('users', 2);
        $this->assertDatabaseCount('teacher_import_batches', 1);
        $this->assertTrue(TeacherImportBatch::query()->firstOrFail()->completed_at->isPast());
    }
}
