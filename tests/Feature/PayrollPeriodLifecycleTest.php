<?php

namespace Tests\Feature;

use App\Models\Admin\User;
use App\Models\Paie\PeriodePaie;
use App\Models\PayrollPeriod;
use App\Models\PayrollRun;
use App\Services\PayrollAuditService;
use App\Services\PayrollCalculationService;
use App\Services\PayrollPeriodCatalogue;
use App\Services\PayrollPeriodLifecycle;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class PayrollPeriodLifecycleTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Schema::create('annee_academiques', function (Blueprint $t) { $t->id(); $t->date('date_debut'); $t->date('date_fin'); $t->boolean('est_active')->default(true); $t->boolean('est_cloturee')->default(false); });
        Schema::create('periode_de_paies', function (Blueprint $t) { $t->id(); $t->string('code'); $t->string('libelle'); $t->timestamps(); });
        Schema::create('payroll_periods', function (Blueprint $t) {
            $t->id(); $t->string('code')->unique(); $t->string('label'); $t->string('status'); $t->date('start_date'); $t->date('end_date');
            $t->integer('version')->default(1); $t->integer('closed_by')->nullable(); $t->dateTime('closed_at')->nullable(); $t->timestamps();
        });
        Schema::create('statuts_enseignant', function (Blueprint $t) { $t->id(); $t->string('code'); $t->string('libelle'); });
        Schema::create('enseignants', function (Blueprint $t) { $t->id(); $t->boolean('est_actif'); $t->softDeletes(); $t->integer('statut_enseignant_id'); $t->string('type_engagement')->nullable(); $t->integer('ia_id'); });
        foreach (['payroll_runs', 'payroll_payslips', 'payroll_elements', 'payroll_attendances'] as $table) {
            Schema::create($table, function (Blueprint $t) { $t->id(); $t->integer('payroll_period_id'); });
        }
        DB::table('annee_academiques')->insert(['date_debut' => '2026-10-01', 'date_fin' => '2027-09-30']);
        DB::table('statuts_enseignant')->insert([['id' => 1, 'code' => 'PC', 'libelle' => 'Contractuels'], ['id' => 2, 'code' => 'VAC', 'libelle' => 'Vacataires'], ['id' => 3, 'code' => 'AUTRE', 'libelle' => 'Autre']]);
        foreach ([[1,1,true], [2,2,true], [1,3,false], [3,4,true]] as $i => [$statut,$ia,$active]) {
            DB::table('enseignants')->insert(['id' => $i+1,'statut_enseignant_id' => $statut,'ia_id' => $ia,'est_actif' => $active]);
        }
        $this->mock(PayrollAuditService::class)->shouldReceive('log')->zeroOrMoreTimes();
    }

    private function actor(): User { return new User(['id' => 1]); }
    private function reference(): PeriodePaie { return PeriodePaie::create(['code' => '11', 'libelle' => 'Novembre']); }
    private function existing(string $status): PayrollPeriod { return PayrollPeriod::create(['code' => '2026-10','label' => 'Octobre','start_date' => '2026-10-01','end_date' => '2026-10-31','status' => $status]); }

    public function test_open_calculates_pc_and_vac_across_all_ias_only(): void
    {
        $this->mock(PayrollCalculationService::class)->shouldReceive('calculate')->once()->withArgs(function ($period,$actor,$ids) {
            $this->assertSame([1,2], $ids);
            $this->assertSame('2026-11', $period->code);
            $period->update(['status' => 'calculated']);
            return true;
        })->andReturn(new PayrollRun());
        $period = app(PayrollPeriodLifecycle::class)->open($this->reference(),$this->actor(),Request::create('/period','POST'));
        $this->assertSame('calculated',$period->status);
    }

    public function test_unclosed_period_blocks_next_opening_without_calling_calculator(): void
    {
        $this->existing('calculated');
        $this->mock(PayrollCalculationService::class)->shouldNotReceive('calculate');
        try { app(PayrollPeriodLifecycle::class)->open($this->reference(),$this->actor(),Request::create('/')); $this->fail('Opening allowed'); }
        catch (ValidationException $e) { $this->assertStringContainsString('Clôturez',$e->getMessage()); }
        $this->assertSame(1,PayrollPeriod::count());
    }

    public function test_calculation_failure_rolls_back_reference_period_and_partial_data(): void
    {
        $this->mock(PayrollCalculationService::class)->shouldReceive('calculate')->once()->andReturnUsing(function ($period) {
            DB::table('payroll_payslips')->insert(['payroll_period_id' => $period->id]);
            throw ValidationException::withMessages(['profile' => 'Diplôme manquant']);
        });
        try {
            DB::transaction(fn () => app(PayrollPeriodLifecycle::class)->open($this->reference(),$this->actor(),Request::create('/')));
            $this->fail('Failure ignored');
        } catch (ValidationException $e) { $this->assertStringContainsString('Diplôme',$e->getMessage()); }
        $this->assertSame(0,PayrollPeriod::count()); $this->assertSame(0,PeriodePaie::count()); $this->assertSame(0,DB::table('payroll_payslips')->count());
    }

    public function test_historical_closed_periods_remain_available_without_reopening(): void
    {
        $period = $this->existing('closed');
        DB::table('annee_academiques')->update(['est_cloturee' => true]);
        $this->assertSame(['2026-10'],app(PayrollPeriodCatalogue::class)->synchronize()->all());
        $this->assertSame('closed',$period->fresh()->status);
    }

    public function test_empty_legacy_period_can_be_closed_without_fabricating_slips(): void
    {
        $period = app(PayrollPeriodLifecycle::class)->close($this->existing('open')->id,$this->actor(),Request::create('/'));
        $this->assertSame('closed',$period->status); $this->assertSame(0,DB::table('payroll_payslips')->count());
    }

    public function test_empty_period_with_unprocessed_inputs_cannot_be_closed(): void
    {
        $period = $this->existing('open');
        DB::table('payroll_elements')->insert(['payroll_period_id' => $period->id]);
        $this->expectException(ValidationException::class);
        app(PayrollPeriodLifecycle::class)->close($period->id,$this->actor(),Request::create('/'));
    }

    public function test_manual_generation_endpoint_is_removed(): void
    {
        $route = collect(app('router')->getRoutes())->first(fn ($route) => $route->uri() === 'api/payroll/actions/{action}');
        $this->assertStringNotContainsString('generate-payslips',$route->wheres['action']);
    }
}
