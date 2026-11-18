<?php

namespace Tests\Feature;

use App\Services\PayrollPeriodAutoClosure;
use App\Services\PayrollPeriodLifecycle;
use App\Models\PayrollPeriod;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PayrollPeriodAutoClosureTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(\Carbon\Carbon::parse('2026-11-30 23:59:59'));
        Schema::create('periode_de_paies', function (Blueprint $t) { $t->id(); $t->date('date_fin')->nullable(); $t->integer('payroll_period_id')->nullable(); });
        Schema::create('payroll_periods', function (Blueprint $t) { $t->id(); $t->string('status'); $t->date('end_date'); $t->timestamps(); });
        Schema::create('payroll_runs', function (Blueprint $t) { $t->id(); $t->integer('payroll_period_id'); $t->integer('calculated_by'); });
        Schema::create('users', function (Blueprint $t) { $t->id(); $t->softDeletes(); });
        DB::table('users')->insert(['id' => 7]);
        DB::table('payroll_periods')->insert(['id' => 1, 'status' => 'calculated', 'end_date' => '2026-11-30']);
        DB::table('payroll_runs')->insert(['payroll_period_id' => 1, 'calculated_by' => 7]);
        DB::table('periode_de_paies')->insert(['date_fin' => '2026-11-30', 'payroll_period_id' => 1]);
    }

    public function test_end_date_is_inclusive_then_close_runs_once_after_midnight(): void
    {
        $this->mock(PayrollPeriodLifecycle::class)->shouldReceive('close')->once()->withArgs(fn ($id, $actor, $request) => $id === 1 && $actor->id === 7)
            ->andReturnUsing(function () { $p = PayrollPeriod::find(1); $p->update(['status' => 'closed']); return $p; });
        $service = app(PayrollPeriodAutoClosure::class);
        $this->assertSame(['closed' => 0, 'failed' => 0], $service->run());
        $this->travelTo(\Carbon\Carbon::parse('2026-12-01 00:00:00'));
        $this->assertSame(['closed' => 1, 'failed' => 0], $service->run());
        $this->assertSame(['closed' => 0, 'failed' => 0], $service->run());
    }

    public function test_integrity_failure_is_reported_and_period_remains_open(): void
    {
        $this->travelTo(\Carbon\Carbon::parse('2026-12-01'));
        $this->mock(PayrollPeriodLifecycle::class)->shouldReceive('close')->once()->andThrow(new \RuntimeException('Intégrité invalide'));
        $this->assertSame(['closed' => 0, 'failed' => 1], app(PayrollPeriodAutoClosure::class)->run());
        $this->assertSame('calculated', PayrollPeriod::find(1)->status);
    }

    public function test_deleting_catalogue_reference_does_not_cancel_monthly_auto_closure(): void
    {
        $this->travelTo(\Carbon\Carbon::parse('2026-12-01'));
        DB::table('periode_de_paies')->update(['payroll_period_id' => null]);
        $this->mock(PayrollPeriodLifecycle::class)->shouldReceive('close')->once()->andReturn(PayrollPeriod::find(1));
        $this->assertSame(['closed' => 1, 'failed' => 0], app(PayrollPeriodAutoClosure::class)->run());
    }
}
