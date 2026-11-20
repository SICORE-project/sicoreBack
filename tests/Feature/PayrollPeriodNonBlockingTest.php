<?php

namespace Tests\Feature;

use App\Models\Admin\User;
use App\Models\PayrollElement;
use App\Models\PayrollPayslip;
use App\Models\PayrollPeriod;
use App\Services\PayrollActionService;
use App\Services\PayrollAuditService;
use App\Services\PayrollCalculationService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Tests\TestCase;

class PayrollPeriodNonBlockingTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Schema::create('payroll_periods', function (Blueprint $t) { $t->id(); $t->string('status'); $t->timestamps(); });
        Schema::create('payroll_elements', function (Blueprint $t) {
            $t->id(); $t->integer('payroll_period_id'); $t->string('status'); $t->integer('version')->default(1);
            $t->boolean('is_exempt')->default(false); $t->string('exemption_reason')->nullable(); $t->integer('validated_by')->nullable(); $t->timestamp('validated_at')->nullable(); $t->timestamps();
        });
        Schema::create('payroll_payslips', function (Blueprint $t) {
            $t->id(); $t->integer('payroll_period_id'); $t->integer('enseignant_id'); $t->string('payment_status'); $t->integer('version')->default(1);
            $t->string('payment_reference')->nullable(); $t->timestamp('paid_at')->nullable(); $t->timestamps();
        });
        $this->mock(PayrollAuditService::class)->shouldReceive('log')->zeroOrMoreTimes();
    }

    public function test_input_validation_and_exemption_work_regardless_of_period_status(): void
    {
        $actor = (new User())->forceFill(['id' => 1]);
        foreach (['open','calculated','validated','closed'] as $status) {
            $period = PayrollPeriod::create(['status' => $status]);
            $this->assertTrue($period->isMutable());
            $element = PayrollElement::create(['payroll_period_id' => $period->id, 'status' => 'draft']);
            app(PayrollActionService::class)->validateInputs($period->id,'elements',$actor,Request::create('/'),null);
            $element->refresh();
            $this->assertSame('validated', $element->status);
            $result = app(PayrollActionService::class)->exemptElement(['payroll_element_id' => $element->id, 'expected_version' => $element->version, 'reason' => 'Exemption autorisée'], $actor, Request::create('/'), null);
            $this->assertTrue($result->is_exempt);
        }
    }

    public function test_payment_does_not_require_period_opening_or_closure(): void
    {
        $period = PayrollPeriod::create(['status' => 'open']);
        $slip = PayrollPayslip::create(['payroll_period_id' => $period->id, 'enseignant_id' => 1, 'payment_status' => 'pending']);
        $result = app(PayrollActionService::class)->markPaid(['payroll_payslip_id' => $slip->id, 'expected_version' => 1, 'payment_reference' => 'TEST-PAID'], (new User())->forceFill(['id' => 1]), Request::create('/'), null);
        $this->assertSame('paid', $result->payment_status);
    }

    public function test_paid_bulletins_remain_protected_even_for_unfiltered_recalculation(): void
    {
        $period = PayrollPeriod::create(['status' => 'closed']);
        PayrollPayslip::create(['payroll_period_id' => $period->id, 'enseignant_id' => 1, 'payment_status' => 'paid']);
        $this->expectException(ConflictHttpException::class);
        $this->expectExceptionMessage('déjà payé');
        app(PayrollCalculationService::class)->calculate($period, (new User())->forceFill(['id' => 1]));
    }
}
