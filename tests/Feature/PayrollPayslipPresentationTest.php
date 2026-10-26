<?php

namespace Tests\Feature;

use App\Models\PayrollPayslip;
use App\Models\PayrollPayslipLine;
use App\Services\PayrollPayslipPresentationService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PayrollPayslipPresentationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Schema::create('rubrique_paies', function (Blueprint $table): void {
            $table->id();
            $table->string('code')->unique();
            $table->string('libelle');
            $table->boolean('est_afficher_bulletin')->default(true);
            $table->decimal('montant', 12, 2)->nullable();
        });
        DB::table('rubrique_paies')->insert([
            'code' => 'PRIME_TEST', 'libelle' => 'Prime définie par administration',
            'est_afficher_bulletin' => true, 'montant' => 999999,
        ]);
    }

    public function test_existing_bulletin_uses_catalogue_labels_without_changing_financial_data(): void
    {
        $payslip = $this->payslip();
        $before = $payslip->toArray();
        $result = app(PayrollPayslipPresentationService::class)->present($payslip);
        $this->assertSame('Prime définie par administration', $result['lines'][0]['label']);
        $this->assertSame('12000.00', $result['lines'][0]['amount']);
        $this->assertSame('earning', $result['lines'][0]['category']);
        $this->assertSame('manual', $result['lines'][0]['source']);
        $this->assertSame('current_catalogue', $result['display_settings']['origin']);
        $this->assertSame($before, $payslip->toArray());
        $this->assertSame(1, DB::table('rubrique_paies')->count());
    }

    public function test_hidden_line_is_not_rendered_but_totals_remain_unchanged(): void
    {
        DB::table('rubrique_paies')->update(['est_afficher_bulletin' => false]);
        $payslip = $this->payslip();
        $result = app(PayrollPayslipPresentationService::class)->present($payslip);
        $this->assertSame([], $result['lines']);
        $this->assertSame(1, $result['display_settings']['hidden_line_count']);
        $this->assertSame('12000.00', $payslip->gross_amount);
        $this->assertSame('12000.00', $payslip->net_amount);
        $this->assertCount(1, $payslip->lines);
    }

    public function test_snapshot_survives_catalogue_rename_hiding_and_deletion(): void
    {
        $service = app(PayrollPayslipPresentationService::class);
        $payslip = $this->payslip();
        $payslip->profile_snapshot = ['bulletin_display' => $service->snapshot($payslip->lines->toArray())];
        DB::table('rubrique_paies')->update(['libelle' => 'Nouveau libellé', 'est_afficher_bulletin' => false]);
        $this->assertSame('Prime définie par administration', $service->present($payslip)['lines'][0]['label']);
        DB::table('rubrique_paies')->delete();
        $result = $service->present($payslip);
        $this->assertSame('Prime définie par administration', $result['lines'][0]['label']);
        $this->assertSame('calculation_snapshot', $result['display_settings']['origin']);
    }

    public function test_unknown_codes_keep_their_labels_and_catalogue_does_not_add_lines(): void
    {
        $payslip = $this->payslip('UNCONFIGURED');
        $result = app(PayrollPayslipPresentationService::class)->present($payslip);
        $this->assertCount(1, $result['lines']);
        $this->assertSame('Libellé du calcul', $result['lines'][0]['label']);
        $this->assertNull($result['lines'][0]['rubric_id']);
    }

    public function test_no_catalogue_does_not_break_legacy_bulletins(): void
    {
        Schema::drop('rubrique_paies');
        $result = app(PayrollPayslipPresentationService::class)->present($this->payslip());
        $this->assertSame('Libellé du calcul', $result['lines'][0]['label']);
    }

    public function test_pdf_code_and_wording_are_used_without_overriding_crud_configuration(): void
    {
        $service = app(PayrollPayslipPresentationService::class);
        $result = $service->present($this->payslip('SALAIRE_BASE'));
        $this->assertSame('10', $result['lines'][0]['display_code']);
        $this->assertSame('SALAIRE DE BASE', $result['lines'][0]['label']);
        DB::table('rubrique_paies')->insert([
            'code' => '10', 'libelle' => 'Libellé décidé dans Paramétrage', 'est_afficher_bulletin' => true,
        ]);
        $result = $service->present($this->payslip('SALAIRE_BASE'));
        $this->assertSame('Libellé décidé dans Paramétrage', $result['lines'][0]['label']);
        DB::table('rubrique_paies')->where('code', '10')->update(['est_afficher_bulletin' => false]);
        $this->assertSame([], $service->present($this->payslip('SALAIRE_BASE'))['lines']);
    }

    private function payslip(string $code = 'PRIME_TEST'): PayrollPayslip
    {
        $payslip = new PayrollPayslip([
            'gross_amount' => 12000, 'net_amount' => 12000, 'profile_snapshot' => null,
        ]);
        $payslip->setRelation('lines', collect([new PayrollPayslipLine([
            'code' => $code, 'label' => 'Libellé du calcul', 'category' => 'earning',
            'amount' => 12000, 'source' => 'manual', 'sort_order' => 10,
        ])]));
        return $payslip;
    }
}
