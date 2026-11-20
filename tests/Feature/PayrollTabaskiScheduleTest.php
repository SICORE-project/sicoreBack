<?php

namespace Tests\Feature;

use App\Models\Admin\User;
use App\Models\PayrollPeriod;
use App\Services\PayrollAuditService;
use App\Services\PayrollTabaskiSchedule;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class PayrollTabaskiScheduleTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Schema::create('users', fn (Blueprint $t) => $t->id());
        DB::table('users')->insert(['id' => 1]);
        Schema::create('annee_academiques', fn (Blueprint $t) => $t->id());
        DB::table('annee_academiques')->insert(['id' => 1]);
        Schema::create('iefs', function (Blueprint $t) { $t->id(); $t->integer('ia_id'); });
        DB::table('iefs')->insert([['id' => 10, 'ia_id' => 1], ['id' => 11, 'ia_id' => 1], ['id' => 20, 'ia_id' => 2]]);
        Schema::create('enseignants', function (Blueprint $t) {
            $t->id(); $t->string('matricule')->nullable(); $t->boolean('est_actif')->default(true); $t->softDeletes(); $t->integer('statut_enseignant_id'); $t->integer('ia_id')->nullable(); $t->integer('ief_id')->nullable();
        });
        DB::table('enseignants')->insert([['id' => 1, 'matricule' => 'TAB-1', 'statut_enseignant_id' => 3, 'ia_id' => 1, 'ief_id' => 10], ['id' => 2, 'matricule' => 'TAB-2', 'statut_enseignant_id' => 3, 'ia_id' => 2, 'ief_id' => 20]]);
        Schema::create('payroll_periods', function (Blueprint $t) {
            $t->id(); $t->string('code'); $t->string('label'); $t->date('start_date'); $t->date('end_date'); $t->string('status'); $t->timestamps();
            $t->integer('employee_count')->default(0); $t->decimal('total_gross',14,2)->default(0); $t->decimal('total_deductions',14,2)->default(0); $t->decimal('total_net',14,2)->default(0); $t->string('checksum')->nullable(); $t->integer('version')->default(1);
        });
        Schema::create('payroll_runs', function (Blueprint $t) {
            $t->id(); $t->integer('payroll_period_id'); $t->integer('employee_count')->default(0); $t->decimal('total_gross',14,2)->default(0); $t->decimal('total_deductions',14,2)->default(0); $t->decimal('total_net',14,2)->default(0); $t->decimal('total_employer_contributions',14,2)->default(0); $t->string('checksum')->nullable(); $t->timestamps();
        });
        Schema::create('payroll_payslips', function (Blueprint $t) {
            $t->id(); $t->integer('payroll_period_id'); $t->integer('payroll_run_id'); $t->integer('enseignant_id'); $t->string('reference');
            $t->text('profile_snapshot')->nullable(); $t->decimal('gross_amount',14,2); $t->decimal('deduction_amount',14,2); $t->decimal('net_amount',14,2); $t->decimal('employer_contribution_amount',14,2)->default(0); $t->integer('version')->default(1); $t->string('payment_status')->default('pending'); $t->timestamps();
        });
        Schema::create('payroll_payslip_lines', function (Blueprint $t) {
            $t->id(); $t->integer('payroll_payslip_id'); $t->string('code'); $t->string('label'); $t->string('category'); $t->decimal('amount',14,2); $t->string('source'); $t->integer('sort_order'); $t->timestamps();
        });
        Schema::create('payroll_elements', function (Blueprint $t) {
            $t->id(); $t->integer('payroll_period_id'); $t->integer('enseignant_id'); $t->string('code'); $t->string('label'); $t->string('category'); $t->string('source'); $t->decimal('amount', 14, 2);
            $t->string('status'); $t->boolean('is_exempt'); $t->integer('created_by'); $t->integer('validated_by'); $t->timestamp('validated_at');
            $t->string('application_scope'); $t->string('application_reference'); $t->integer('application_statut_enseignant_id'); $t->integer('application_ia_id'); $t->integer('application_ief_id')->nullable(); $t->integer('applied_by'); $t->timestamp('applied_at'); $t->timestamps();
            $t->unique(['payroll_period_id', 'enseignant_id', 'code']);
        });
        (require database_path('migrations/2026_11_16_150000_create_payroll_tabaski_schedules.php'))->up();
        $this->mock(PayrollAuditService::class)->shouldReceive('log')->zeroOrMoreTimes();
    }

    private function apply(bool $deduction = false, string $month = '2027-01', array $ias = [1], array $filters = []): array
    {
        return app(PayrollTabaskiSchedule::class)->apply(array_replace(['start_month' => $month, 'statut_enseignant_id' => 3, 'ia_ids' => $ias], $filters), $deduction, (new User())->forceFill(['id' => 1]), Request::create('/tabaski', 'POST'), null);
    }
    private function period(string $month): PayrollPeriod
    {
        $date = \Carbon\Carbon::parse($month.'-01');
        return PayrollPeriod::create(['code' => $month, 'label' => $month, 'start_date' => $date, 'end_date' => $date->copy()->endOfMonth(), 'status' => 'open']);
    }
    private function materialize(PayrollPeriod $period, array $ids = [1,2,3]): void
    {
        DB::transaction(fn () => app(PayrollTabaskiSchedule::class)->materialize($period, $ids, (new User())->forceFill(['id' => 1])));
        // Complete the advance bulletin, as the calculation transaction does in production.
        foreach (DB::table('payroll_elements')->where('payroll_period_id', $period->id)->where('code', 'TABASKI_AVANCE')->get() as $element) {
            $slip = \App\Models\PayrollPayslip::where('payroll_period_id', $period->id)->where('enseignant_id', $element->enseignant_id)->first() ?? $this->slip($period, $element->enseignant_id);
            $slip->lines()->firstOrCreate(['code' => 'TABASKI_AVANCE'], ['label' => 'Avance Tabaski', 'category' => 'earning', 'amount' => 100000, 'source' => 'system', 'sort_order' => 80]);
        }
    }

    private function slip(PayrollPeriod $period, int $teacher = 1, bool $paid = false): \App\Models\PayrollPayslip
    {
        $run = \App\Models\PayrollRun::firstOrCreate(['payroll_period_id' => $period->id]);
        $slip = \App\Models\PayrollPayslip::create(['payroll_period_id' => $period->id, 'payroll_run_id' => $run->id,
            'enseignant_id' => $teacher, 'reference' => 'BS-'.$period->code.'-'.$teacher,
            'gross_amount' => 200000, 'deduction_amount' => 20000, 'net_amount' => 180000, 'payment_status' => $paid ? 'paid' : 'pending',
            'profile_snapshot' => ['bulletin_display' => ['SALAIRE_BASE' => ['label' => 'SALAIRE BRUT', 'visible' => true]]]]);
        $slip->lines()->create(['code' => 'SALAIRE_BASE', 'label' => 'Salaire historique', 'category' => 'earning', 'amount' => 200000, 'source' => 'diploma_salary', 'sort_order' => 10]);
        $slip->lines()->create(['code' => 'IMPR', 'label' => 'Impôt historique', 'category' => 'deduction', 'amount' => 20000, 'source' => 'payroll_rubric', 'sort_order' => 60]);
        return $slip->fresh();
    }

    public function test_selected_december_is_updated_even_if_june_next_year_already_exists(): void
    {
        $december = $this->period('2026-12'); $december->update(['status' => 'closed']);
        $slip = $this->slip($december);
        $other = $this->slip($december, 2); $otherBefore = $other->toArray();
        $june = $this->slip($this->period('2027-06')); $juneBefore = $june->toArray();
        $lines = $slip->lines()->get()->toArray();
        $result = $this->apply(false, '2026-12');
        $this->assertSame(1, $result['updated_bulletins']);
        $this->assertEquals(280000, $slip->fresh()->net_amount);
        $this->assertEquals(300000, $slip->fresh()->gross_amount);
        $this->assertEquals(100000, $slip->lines()->where('code','TABASKI_AVANCE')->value('amount'));
        $this->assertSame($lines, $slip->lines()->where('code','!=','TABASKI_AVANCE')->get()->toArray());
        $this->assertSame($otherBefore, $other->fresh()->toArray());
        $this->assertSame($juneBefore, $june->fresh()->toArray());
        $this->assertEquals(460000, $december->fresh()->total_net);
        $this->assertSame($december->fresh()->checksum, $december->run()->first()->checksum);
    }

    public function test_paid_selected_bulletin_is_protected_and_whole_application_is_rolled_back(): void
    {
        $slip = $this->slip($this->period('2026-12'), 1, true); $before = $slip->toArray();
        try { $this->apply(false, '2026-12'); $this->fail('Paid bulletin changed'); }
        catch (ValidationException $e) { $this->assertStringContainsString('déjà payé', $e->getMessage()); }
        $this->assertSame($before, $slip->fresh()->toArray());
        $this->assertSame(0, DB::table('payroll_tabaski_allocations')->count());
        $this->assertSame(0, DB::table('payroll_elements')->count());
    }

    public function test_existing_monthly_statements_receive_exactly_ten_calendar_deductions(): void
    {
        $this->slip($this->period('2026-12'));
        $this->apply(false, '2026-12');
        for ($i = 0; $i < 11; $i++) $this->slip($this->period(\Carbon\Carbon::parse('2027-01-01')->addMonths($i)->format('Y-m')));
        $result = $this->apply(true, '2027-01');
        $this->assertSame(10, $result['updated_bulletins']);
        $this->assertSame(10, DB::table('payroll_tabaski_installments')->count());
        $this->assertEquals(100000, DB::table('payroll_payslip_lines')->where('code','TABASKI_RETENUE')->sum('amount'));
        $november = PayrollPeriod::where('code','2027-11')->firstOrFail();
        $this->assertEquals(180000, $november->payslips()->first()->net_amount);
    }

    public function test_fixed_beneficiaries_and_ten_deductions_survive_closed_periods_and_year_change(): void
    {
        $this->apply(false, '2026-12');
        DB::table('enseignants')->insert(['id' => 3,'statut_enseignant_id' => 3,'ia_id' => 1]);
        $p = $this->period('2026-12'); $this->materialize($p);
        $this->assertEquals(100000, DB::table('payroll_elements')->where('code','TABASKI_AVANCE')->sum('amount'));
        $p->update(['status' => 'closed']);
        $summary = $this->apply(true, '2027-01');
        $this->assertSame(1, $summary['affected_teachers']);
        for ($i = 0; $i < 11; $i++) {
            $month = \Carbon\Carbon::parse('2027-01-01')->addMonths($i)->format('Y-m');
            $p = $this->period($month); $this->materialize($p); $this->materialize($p);
            $p->update(['status' => 'closed']);
        }
        $this->assertSame(10, DB::table('payroll_tabaski_installments')->count());
        $this->assertEquals(100000, DB::table('payroll_elements')->where('code','TABASKI_RETENUE')->sum('amount'));
        $this->assertSame(0, DB::table('payroll_elements')->whereIn('enseignant_id',[2,3])->count());
        $this->assertSame(0, DB::table('payroll_elements')->where('payroll_period_id',$p->id)->count());
    }

    public function test_programmed_advance_without_bulletin_cannot_activate_repayment(): void
    {
        $this->apply();
        $this->expectException(ValidationException::class);
        $this->apply(true);
    }

    public function test_no_recovery_without_advance(): void
    {
        $this->expectException(ValidationException::class); $this->apply(true);
    }

    public function test_advance_marker_without_bulletin_line_cannot_authorize_repayment(): void
    {
        $this->apply();
        $period = $this->period('2027-01');
        DB::table('payroll_tabaski_allocations')->update(['advance_period_id' => $period->id]);
        $this->slip($period); // Salary bulletin exists, but contains no advance.
        try { $this->apply(true, '2027-02'); $this->fail('Missing advance accepted'); }
        catch (ValidationException $e) {
            $this->assertNull(DB::table('payroll_tabaski_allocations')->value('repayment_from_month'));
            $this->assertSame(0, DB::table('payroll_tabaski_installments')->count());
        }
    }

    public function test_orphan_deduction_is_not_authorized_and_not_shown_in_report(): void
    {
        $element = new \App\Models\PayrollElement(['enseignant_id' => 1, 'payroll_period_id' => 1,
            'code' => 'TABASKI_RETENUE', 'amount' => 10000, 'application_reference' => 'ORPHAN']);
        $this->assertFalse(app(PayrollTabaskiSchedule::class)->allowsDeduction($element));
        Schema::create('ias', function (Blueprint $t) { $t->id(); $t->string('libelle'); });
        Schema::create('statuts_enseignant', function (Blueprint $t) { $t->id(); $t->string('libelle'); });
        Schema::table('iefs', fn (Blueprint $t) => $t->string('libelle')->nullable());
        // An old element must not be turned into a beneficiary or repayment debt.
        $this->apply(); $this->materialize($this->period('2027-01'));
        DB::table('payroll_elements')->update(['code' => 'TABASKI_RETENUE', 'application_reference' => 'ORPHAN']);
        $report = app(\App\Services\PayrollTabaskiReport::class)->report('TABASKI_RETENUE', \App\Models\Enseignant::all());
        $this->assertCount(0, $report['rows']);
    }

    public function test_optional_ief_limits_multi_ia_selection_to_its_teachers(): void
    {
        DB::table('enseignants')->insert(['id' => 3,'matricule' => 'TAB-3','statut_enseignant_id' => 3,'ia_id' => 1,'ief_id' => 11]);
        $result = $this->apply(false, '2027-01', [1,2], ['ief_id' => 10]);
        $this->assertSame(1, $result['affected_teachers']);
        $this->assertEquals(1, DB::table('payroll_tabaski_allocations')->value('enseignant_id'));
    }

    public function test_exact_matricule_limits_advance_and_repayment_to_one_beneficiary(): void
    {
        $this->apply(false, '2027-01', [1,2], ['matricule' => ' tab-2 ']);
        $this->assertEquals(2, DB::table('payroll_tabaski_allocations')->value('enseignant_id'));
        $this->materialize($this->period('2027-01'));
        $this->apply(true, '2027-02', [1,2], ['ief_id' => 20, 'matricule' => 'TAB-2']);
        $this->assertSame('2027-02-01', DB::table('payroll_tabaski_allocations')->value('repayment_from_month'));
        $this->materialize($this->period('2027-01'));
        $this->materialize($this->period('2027-02'));
        $this->assertSame(0, DB::table('payroll_elements')->where('enseignant_id',1)->count());
        $this->assertEquals(10000, DB::table('payroll_elements')->where('enseignant_id',2)->where('code','TABASKI_RETENUE')->sum('amount'));
    }

    public function test_ief_outside_selected_ias_is_rejected(): void
    {
        $this->expectException(ValidationException::class);
        $this->apply(false, '2027-01', [1], ['ief_id' => 20]);
    }

    public function test_matricule_in_another_ief_does_not_expand_selection(): void
    {
        $this->expectException(ValidationException::class);
        $this->apply(false, '2027-01', [1,2], ['ief_id' => 10, 'matricule' => 'TAB-2']);
    }

    public function test_new_teacher_identified_by_matricule_has_no_repayment_without_advance(): void
    {
        $this->apply();
        DB::table('enseignants')->insert(['id' => 3,'matricule' => 'TAB-NEW','statut_enseignant_id' => 3,'ia_id' => 1,'ief_id' => 10]);
        try { $this->apply(true, '2027-02', [1], ['matricule' => 'TAB-NEW']); $this->fail('Non-beneficiary accepted'); }
        catch (ValidationException $e) { $this->assertNull(DB::table('payroll_tabaski_allocations')->value('repayment_from_month')); }
    }

    public function test_scheduling_never_opens_future_months_and_reactivation_does_not_reset_counter(): void
    {
        $this->apply();
        $this->assertSame(0, PayrollPeriod::count());
        $this->materialize($this->period('2027-01'));
        $this->apply(true, '2027-02');
        $this->assertSame(1, PayrollPeriod::count());
        $this->materialize($this->period('2027-02'));
        try { $this->apply(true, '2027-03'); $this->fail('Schedule reset'); }
        catch (ValidationException $e) { $this->assertSame(1, DB::table('payroll_tabaski_installments')->count()); }
        $this->assertSame('2027-02-01', DB::table('payroll_tabaski_allocations')->value('repayment_from_month'));
    }

    public function test_duplicate_application_does_not_double_an_outstanding_advance(): void
    {
        $this->apply();
        try { $this->apply(); $this->fail('Duplicate allowed'); } catch (ValidationException $e) { $this->assertSame(1,DB::table('payroll_tabaski_allocations')->count()); }
    }

    public function test_existing_period_without_salary_statement_keeps_advance_pending_for_exact_month(): void
    {
        $p = $this->period('2027-01'); $p->update(['status' => 'closed']);
        $this->apply();
        $this->assertNull(DB::table('payroll_tabaski_allocations')->value('advance_period_id'));
        $this->assertSame(0, DB::table('payroll_payslips')->count());
        $this->materialize($this->period('2027-02'));
        $this->assertSame(0, DB::table('payroll_elements')->count());
    }

    public function test_failed_calculation_rolls_back_installment_and_payment_markers(): void
    {
        $this->apply(); $p = $this->period('2027-01');
        try { DB::transaction(function () use ($p) { $this->materialize($p); throw new \RuntimeException('Failed payroll'); }); } catch (\RuntimeException $e) {}
        $this->assertNull(DB::table('payroll_tabaski_allocations')->value('advance_period_id'));
        $this->assertSame(0, DB::table('payroll_elements')->count());
        $this->materialize($p);
        $this->assertSame(1, DB::table('payroll_elements')->count());
    }

    public function test_inactive_teacher_is_not_debited_and_missing_month_does_not_double_monthly_amount(): void
    {
        $this->apply(); $this->materialize($this->period('2027-01')); $this->apply(true, '2027-02');
        $this->materialize($this->period('2027-02'), [2]);
        $this->assertSame(0, DB::table('payroll_tabaski_installments')->count());
        $this->materialize($this->period('2027-04'), [1,2]);
        $this->assertEquals(10000, DB::table('payroll_elements')->where('code','TABASKI_RETENUE')->sum('amount'));
    }
}
