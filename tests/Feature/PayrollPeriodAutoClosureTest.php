<?php

namespace Tests\Feature;

use App\Services\PayrollPeriodAutoClosure;
use App\Services\PayrollPeriodLifecycle;
use Tests\TestCase;

class PayrollPeriodAutoClosureTest extends TestCase
{
    public function test_old_worker_no_longer_changes_periods_or_requires_database_access(): void
    {
        $this->mock(PayrollPeriodLifecycle::class)->shouldNotReceive('close');
        $this->travelTo(\Carbon\Carbon::parse('2030-12-31'));
        $this->assertSame(['closed' => 0, 'failed' => 0], app(PayrollPeriodAutoClosure::class)->run());
        $this->assertSame(['closed' => 0, 'failed' => 0], app(PayrollPeriodAutoClosure::class)->run());
    }

    public function test_close_expired_is_not_registered_as_a_scheduled_task(): void
    {
        $events = app(\Illuminate\Console\Scheduling\Schedule::class)->events();
        foreach ($events as $event) $this->assertStringNotContainsString('payroll:close-expired', $event->command ?? '');
    }
}