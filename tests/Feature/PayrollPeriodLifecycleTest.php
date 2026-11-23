<?php

namespace Tests\Feature;

use App\Models\Admin\User;
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
        Schema::create('periode_de_paies', function (Blueprint $t) { $t->id(); $t->string('code'); $t->string('libelle'); $t->unsignedTinyInteger('numero_mois')->nullable()->unique(); $t->timestamps(); });
        Schema::create('payroll_periods', function (Blueprint $t) {
            $t->id(); $t->string('code')->unique(); $t->string('label'); $t->string('status'); $t->date('start_date'); $t->date('end_date'); $t->unsignedBigInteger('annee_academique_id')->nullable();
            $t->integer('version')->default(1); $t->integer('closed_by')->nullable(); $t->dateTime('closed_at')->nullable(); $t->timestamps();
        });
        Schema::create('statuts_enseignant', function (Blueprint $t) { $t->id(); $t->string('code'); $t->string('libelle'); });
        Schema::create('enseignants', function (Blueprint $t) { $t->id(); $t->boolean('est_actif'); $t->softDeletes(); $t->integer('statut_enseignant_id'); $t->string('type_engagement')->nullable(); $t->integer('ia_id'); });
        foreach (['payroll_runs', 'payroll_payslips', 'payroll_elements', 'payroll_attendances'] as $table) {
            Schema::create($table, function (Blueprint $t) { $t->id(); $t->integer('payroll_period_id'); });
        }
        DB::table('annee_academiques')->insert(['id' => 1, 'date_debut' => '2026-10-01', 'date_fin' => '2027-09-30']);
        foreach ([7 => 'JUILLET', 10 => 'OCTOBRE', 11 => 'NOVEMBRE'] as $n => $label) {
            DB::table('periode_de_paies')->insert(['code' => substr($label, 0, 3), 'libelle' => $label, 'numero_mois' => $n]);
        }
        DB::table('statuts_enseignant')->insert([['id' => 1, 'code' => 'PC', 'libelle' => 'Contractuels'], ['id' => 2, 'code' => 'VAC', 'libelle' => 'Vacataires'], ['id' => 3, 'code' => 'AUTRE', 'libelle' => 'Autre']]);
        foreach ([[1,1,true], [2,2,true], [1,3,false], [3,4,true]] as $i => [$statut,$ia,$active]) {
            DB::table('enseignants')->insert(['id' => $i+1,'statut_enseignant_id' => $statut,'ia_id' => $ia,'est_actif' => $active]);
        }
        $this->mock(PayrollAuditService::class)->shouldReceive('log')->zeroOrMoreTimes();
        $this->mock(\App\Services\PayrollTabaskiSchedule::class)->shouldReceive('materialize')->zeroOrMoreTimes();
    }

    private function actor(): User { return new User(['id' => 1]); }

    private function open(int $month, int $year = 1): PayrollPeriod
    {
        return app(PayrollPeriodLifecycle::class)->openForMonth($month, $year, $this->actor(), Request::create('/period', 'POST'), null);
    }

    private function existing(string $status, ?int $year = 1): PayrollPeriod
    {
        return PayrollPeriod::create(['code' => '2026-10','label' => 'Octobre','start_date' => '2026-10-01','end_date' => '2026-10-31','status' => $status, 'annee_academique_id' => $year]);
    }

    public function test_open_calculates_pc_and_vac_across_all_ias_only(): void
    {
        $this->mock(PayrollCalculationService::class)->shouldReceive('calculate')->once()->withArgs(function ($period,$actor,$ids) {
            $this->assertSame([1,2], $ids);
            $this->assertSame('2026-11', $period->code);
            $period->update(['status' => 'calculated']);
            return true;
        })->andReturn(new PayrollRun());
        $this->assertSame('calculated', $this->open(11)->status);
    }

    public function test_opened_period_is_linked_to_its_academic_year_and_catalogue_is_untouched(): void
    {
        $this->mock(PayrollCalculationService::class)->shouldReceive('calculate')->once()->andReturn(new PayrollRun());
        $before = DB::table('periode_de_paies')->orderBy('id')->get()->toArray();
        $period = $this->open(11);
        $this->assertSame(1, (int) $period->annee_academique_id);
        $this->assertSame('2026-11-01', $period->start_date->toDateString());
        $this->assertSame('2026-11-30', $period->end_date->toDateString());
        $this->assertEquals($before, DB::table('periode_de_paies')->orderBy('id')->get()->toArray());
    }

    public function test_registering_next_month_does_not_prematurely_close_previous_month(): void
    {
        $previous = $this->existing('open');
        $this->mock(PayrollCalculationService::class)->shouldReceive('calculate')->once()->andReturnUsing(function () use ($previous) {
            $this->assertSame('open', $previous->fresh()->status);
            return new PayrollRun();
        });
        $this->open(11);
        $this->assertSame(2,PayrollPeriod::count());
    }

    public function test_calculation_failure_rolls_back_period_and_partial_data(): void
    {
        $this->mock(PayrollCalculationService::class)->shouldReceive('calculate')->once()->andReturnUsing(function ($period) {
            DB::table('payroll_payslips')->insert(['payroll_period_id' => $period->id]);
            throw ValidationException::withMessages(['profile' => 'Diplôme manquant']);
        });
        try {
            DB::transaction(fn () => $this->open(11));
            $this->fail('Failure ignored');
        } catch (ValidationException $e) { $this->assertStringContainsString('Diplôme',$e->getMessage()); }
        $this->assertSame(0,PayrollPeriod::count()); $this->assertSame(3,DB::table('periode_de_paies')->count()); $this->assertSame(0,DB::table('payroll_payslips')->count());
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

    public function test_legacy_closure_metadata_does_not_lock_unprocessed_inputs(): void
    {
        $period = $this->existing('open');
        DB::table('payroll_elements')->insert(['payroll_period_id' => $period->id]);
        $closed = app(PayrollPeriodLifecycle::class)->close($period->id,$this->actor(),Request::create('/'));
        $this->assertTrue($closed->isMutable());
        $this->assertSame(1, DB::table('payroll_elements')->count());
    }

    public function test_manual_generation_endpoint_is_removed(): void
    {
        $route = collect(app('router')->getRoutes())->first(fn ($route) => $route->uri() === 'api/payroll/actions/{action}');
        $this->assertStringNotContainsString('generate-payslips',$route->wheres['action']);
    }

    public function test_existing_month_does_not_recalculate_or_reopen_history(): void
    {
        $existing = $this->existing('closed');
        $this->mock(PayrollCalculationService::class)->shouldNotReceive('calculate');
        $period = $this->open(10);
        $this->assertSame($existing->id, $period->id);
        $this->assertSame('closed', $period->status);
        $this->assertSame(1, PayrollPeriod::count());
    }

    public function test_existing_period_without_year_gets_linked_on_reopen(): void
    {
        $existing = $this->existing('calculated', null);
        $this->mock(PayrollCalculationService::class)->shouldNotReceive('calculate');
        $this->assertSame(1, (int) $this->open(10)->annee_academique_id);
        $this->assertSame(1, (int) $existing->fresh()->annee_academique_id);
    }

    public function test_next_month_failure_preserves_previous_month(): void
    {
        $previous = $this->existing('open');
        $this->mock(PayrollCalculationService::class)->shouldReceive('calculate')->once()->andThrow(new \RuntimeException('Dossier incomplet'));
        try { $this->open(11); $this->fail('Failed calculation ignored'); }
        catch (\RuntimeException $e) { $this->assertSame('open', $previous->fresh()->status); }
        $this->assertSame(1, PayrollPeriod::count());
    }

    public function test_inactive_closed_academic_year_allows_month_creation(): void
    {
        DB::table('annee_academiques')->update(['est_active' => false, 'est_cloturee' => true]);
        $this->mock(PayrollCalculationService::class)->shouldReceive('calculate')->once()->andReturn(new PayrollRun());
        $this->assertSame('2027-07', $this->open(7)->code);
    }

    public function test_same_month_is_opened_in_the_chosen_year_only(): void
    {
        DB::table('annee_academiques')->insert(['id' => 2, 'date_debut' => '2027-10-01', 'date_fin' => '2028-09-30', 'est_active' => false, 'est_cloturee' => true]);
        $this->mock(PayrollCalculationService::class)->shouldReceive('calculate')->twice()->andReturn(new PayrollRun());
        $this->assertSame('2027-07', $this->open(7, 1)->code);
        $second = $this->open(7, 2);
        $this->assertSame('2028-07', $second->code);
        $this->assertSame(2, (int) $second->annee_academique_id);
    }

    public function test_month_outside_academic_year_is_refused(): void
    {
        DB::table('annee_academiques')->update(['date_fin' => '2027-06-22']);
        $this->mock(PayrollCalculationService::class)->shouldNotReceive('calculate');
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('n’appartient pas');
        $this->open(7);
    }

    public function test_month_missing_from_catalogue_is_refused(): void
    {
        $this->expectException(ValidationException::class);
        $this->open(12);
    }
}
