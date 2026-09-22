<?php

namespace Tests\Unit;

use App\Models\PayrollPeriod;
use App\Services\PayrollActionService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use ReflectionMethod;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Tests\TestCase;

class PayrollTabaskiPeriodTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('payroll_periods', function (Blueprint $table): void {
            $table->id();
            $table->string('code')->unique();
            $table->string('label');
            $table->date('start_date');
            $table->date('end_date');
            $table->string('status');
            $table->unsignedInteger('version')->default(1);
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('payroll_periods');

        parent::tearDown();
    }

    public function test_les_mois_tabaski_creent_leurs_periodes_sans_seeder(): void
    {
        $periods = $this->resolveMonths(collect([10, 11, 1]));

        $this->assertSame(['2027-10', '2027-11', '2028-01'], $periods->pluck('code')->all());
        $this->assertSame(3, PayrollPeriod::query()->count());
        $this->assertTrue($periods->every(fn (PayrollPeriod $period): bool => $period->isMutable()));
    }

    public function test_une_periode_deja_fermee_ne_peut_pas_etre_reouverte(): void
    {
        PayrollPeriod::query()->create([
            'code' => '2027-10',
            'label' => 'Octobre 2027',
            'start_date' => '2027-10-01',
            'end_date' => '2027-10-31',
            'status' => PayrollPeriod::STATUS_CLOSED,
        ]);

        $this->expectException(ConflictHttpException::class);
        $this->resolveMonths(collect([10]));
    }

    private function resolveMonths(\Illuminate\Support\Collection $months): \Illuminate\Support\Collection
    {
        $method = new ReflectionMethod(PayrollActionService::class, 'periodsForAcademicMonths');

        return $method->invoke(
            app(PayrollActionService::class),
            (object) ['date_debut' => '2027-10-01', 'date_fin' => '2028-09-30'],
            $months,
            'months',
        );
    }
}
