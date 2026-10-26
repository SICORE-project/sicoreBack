<?php

namespace Tests\Feature;

use App\Services\PayrollRubricAmounts;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PayrollRubricAmountsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Schema::create('rubrique_paies', function (Blueprint $table) {
            $table->id(); $table->string('code')->unique(); $table->string('libelle');
            $table->string('type')->default('retenue'); $table->string('periodicite')->default('mensuelle');
            $table->boolean('est_actif')->default(true); $table->decimal('montant', 12, 2)->nullable(); $table->timestamps();
        });
    }

    public function test_amounts_and_provenance_are_read_from_the_crud_on_each_resolution(): void
    {
        $this->configure();
        $service = app(PayrollRubricAmounts::class);
        $first = $service->resolve('contractuel');
        $this->assertSame(12500.0, $first['impr']);
        $this->assertSame(500.0, $first['trimf']);
        $this->assertSame('IPM', $first['deduction_rubrics']['ipm']['code']);
        DB::table('rubrique_paies')->where('code', 'IPM')->update(['montant' => 0]);
        $this->assertSame(0.0, $service->resolve('contractuel')['ipm']);
        $this->assertSame(4500.0, $first['ipm']);
    }

    public function test_vac_uses_only_impr_and_trimf_and_accepts_official_codes(): void
    {
        DB::table('rubrique_paies')->insert([
            ['code' => 'I061', 'libelle' => 'IMPR', 'montant' => 3000],
            ['code' => 'I090', 'libelle' => 'TRIMF', 'montant' => 500],
        ]);
        $result = app(PayrollRubricAmounts::class)->resolve('vacataire');
        $this->assertSame(3000.0, $result['impr']);
        $this->assertSame(0.0, $result['ipm']);
        $this->assertSame(0.0, $result['checkoff']);
    }

    public function test_null_amount_never_becomes_an_implicit_zero(): void
    {
        $this->configure();
        DB::table('rubrique_paies')->where('code', 'IMPR')->update(['montant' => null]);
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('IMPR');
        app(PayrollRubricAmounts::class)->resolve('contractuel');
    }

    public function test_missing_rubrics_produce_one_actionable_error_for_the_catalogue(): void
    {
        try {
            app(PayrollRubricAmounts::class)->resolve('contractuel');
            $this->fail('Configuration manquante acceptée');
        } catch (ValidationException $exception) {
            $message = $exception->errors()['rubriques_paie'][0];
            foreach (['Paramétrage', 'IMPR', 'TRIMF', 'IPM', 'CHECKOFF_UES'] as $word) {
                $this->assertStringContainsString($word, $message);
            }
        }
    }

    public function test_inconsistent_aliases_are_rejected_instead_of_selecting_a_random_amount(): void
    {
        $this->configure();
        DB::table('rubrique_paies')->insert(['code' => 'I061', 'libelle' => 'Autre IMPR', 'montant' => 50000]);
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('contradictoires');
        app(PayrollRubricAmounts::class)->resolve('contractuel');
    }

    public function test_only_active_monthly_deductions_are_accepted(): void
    {
        $this->configure();
        DB::table('rubrique_paies')->where('code', 'IPM')->update(['est_actif' => false, 'periodicite' => 'ponctuelle', 'type' => 'gain']);
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('IPM');
        app(PayrollRubricAmounts::class)->resolve('contractuel');
    }

    private function configure(): void
    {
        foreach (['IMPR' => 12500, 'TRIMF' => 500, 'IPM' => 4500, 'CHECKOFF_UES' => 1000] as $code => $amount) {
            DB::table('rubrique_paies')->insert(['code' => $code, 'libelle' => $code, 'montant' => $amount]);
        }
    }
}
