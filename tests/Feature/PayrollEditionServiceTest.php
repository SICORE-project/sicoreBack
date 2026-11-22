<?php

namespace Tests\Feature;

use App\Models\Admin\User;
use App\Models\PayrollPeriod;
use App\Models\PayrollRun;
use App\Services\Administration\IaScope;
use App\Services\PayrollAuditService;
use App\Services\PayrollCalculationService;
use App\Services\PayrollEditionService;
use App\Services\PayrollTabaskiSchedule;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Tests\TestCase;

class PayrollEditionServiceTest extends TestCase
{
    private PayrollPeriod $period;

    protected function setUp(): void
    {
        parent::setUp();
        Schema::create('payroll_periods', function (Blueprint $t) { $t->id(); $t->string('code'); $t->string('label')->nullable(); $t->string('status'); $t->date('start_date'); $t->date('end_date'); $t->timestamps(); });
        Schema::create('users', function (Blueprint $t) { $t->id(); $t->string('nom')->nullable(); $t->string('prenom')->nullable(); $t->timestamps(); $t->softDeletes(); });
        Schema::create('payroll_edition_validations', function (Blueprint $t) { $t->id(); $t->integer('payroll_period_id'); $t->integer('ia_id'); $t->integer('validated_by')->nullable(); $t->timestamp('validated_at'); $t->integer('payslip_count')->default(0); $t->decimal('total_net', 15, 2)->default(0); $t->timestamp('statement_generated_at')->nullable(); $t->integer('statement_generated_by')->nullable(); $t->timestamps(); });
        Schema::create('payroll_edition_changes', function (Blueprint $t) { $t->id(); $t->integer('payroll_period_id')->nullable(); $t->integer('ia_id'); $t->integer('enseignant_id'); $t->json('changes'); $t->integer('changed_by')->nullable(); $t->integer('validation_id')->nullable(); $t->timestamp('validated_at')->nullable(); $t->timestamps(); });
        Schema::create('statuts_enseignant', function (Blueprint $t) { $t->id(); $t->string('code'); $t->string('libelle'); });
        Schema::create('enseignants', function (Blueprint $t) { $t->id(); $t->boolean('est_actif'); $t->softDeletes(); $t->integer('statut_enseignant_id'); $t->string('type_engagement')->nullable(); $t->integer('ia_id'); $t->date('date_prise_service')->nullable(); $t->string('matricule')->nullable(); $t->string('prenom')->nullable(); $t->string('nom')->nullable(); });
        Schema::create('payroll_payslips', function (Blueprint $t) { $t->id(); $t->integer('payroll_period_id'); $t->integer('enseignant_id'); $t->integer('ia_id')->nullable(); $t->string('reference')->nullable(); $t->string('payment_status')->default('pending'); $t->decimal('net_amount', 15, 2)->default(0); $t->timestamps(); });
        DB::table('statuts_enseignant')->insert([['id' => 1, 'code' => 'PC', 'libelle' => 'Contractuels'], ['id' => 3, 'code' => 'FONC', 'libelle' => 'Fonctionnaires']]);
        DB::table('users')->insert(['id' => 1, 'prenom' => 'Awa', 'nom' => 'Sall']);
        $this->period = PayrollPeriod::create(['code' => '2026-11', 'label' => 'NOVEMBRE 2026', 'status' => 'calculated', 'start_date' => '2026-11-01', 'end_date' => '2026-11-30']);
        $this->mock(IaScope::class)->shouldReceive('id')->andReturn(2);
        $this->mock(PayrollAuditService::class)->shouldReceive('log')->zeroOrMoreTimes();
        $this->mock(PayrollTabaskiSchedule::class)->shouldReceive('materialize')->zeroOrMoreTimes();
    }

    private function actor(): User { return User::findOrFail(1); }

    /** Modification de fiche faite par l'IA (Gestion du personnel), hors de tout mois. */
    private function change(int $ia, int $teacher = 1, string $at = '+1 minute'): void
    {
        DB::table('payroll_edition_changes')->insert(['payroll_period_id' => null, 'ia_id' => $ia, 'enseignant_id' => $teacher, 'changes' => '{}', 'created_at' => now()->modify($at)]);
    }

    public function test_validated_month_stays_validated_and_later_changes_go_to_next_month(): void
    {
        $edition = app(PayrollEditionService::class);
        $this->assertSame('jamais', $edition->status($this->period, 2)['status']);
        DB::table('enseignants')->insert(['id' => 7, 'est_actif' => true, 'statut_enseignant_id' => 1, 'ia_id' => 2, 'matricule' => 'MAT7', 'prenom' => 'Ali', 'nom' => 'Ba']);

        DB::table('payroll_edition_validations')->insert(['payroll_period_id' => $this->period->id, 'ia_id' => 2, 'validated_by' => 1, 'validated_at' => now()]);
        $this->change(2, 7); // après la validation de novembre
        $status = $edition->status($this->period, 2);
        $this->assertSame('validee', $status['status']);
        $this->assertSame('Awa Sall', $status['validated_by']);
        $this->assertSame([2], $edition->validatedIas($this->period));
        // Le bandeau de novembre l'annonce pour le mois suivant…
        $this->assertSame(1, count($edition->pageData($this->period, 2, true)['changes']));

        // … et décembre, non validé, l'affiche comme à prendre en compte.
        $december = PayrollPeriod::create(['code' => '2026-12', 'label' => 'DECEMBRE 2026', 'status' => 'calculated', 'start_date' => '2026-12-01', 'end_date' => '2026-12-31']);
        $next = $edition->status($december, 2);
        $this->assertSame('jamais', $next['status']);
        $this->assertSame([7], $next['pending_teacher_ids']);
    }

    public function test_statement_is_generated_once(): void
    {
        $edition = app(PayrollEditionService::class);
        // Mois non validé : aucune génération enregistrée.
        $edition->markStatementGenerated($this->actor(), $this->period->id);
        $this->assertSame([], $edition->monthStates(2));

        DB::table('payroll_edition_validations')->insert(['payroll_period_id' => $this->period->id, 'ia_id' => 2, 'validated_at' => now()->subMinute()]);
        $this->assertFalse($edition->monthStates(2)['p'.$this->period->id]['statement_generated']);
        $edition->markStatementGenerated($this->actor(), $this->period->id);
        $first = DB::table('payroll_edition_validations')->value('statement_generated_at');
        $this->assertNotNull($first);
        $this->travel(5)->minutes();
        $edition->markStatementGenerated($this->actor(), $this->period->id);
        $this->assertSame($first, DB::table('payroll_edition_validations')->value('statement_generated_at'));

        // Une mise à jour postérieure ne rouvre pas le mois : l'état reste « Consulter ».
        $this->change(2);
        $state = $edition->monthStates(2)['p'.$this->period->id];
        $this->assertSame('validee', $state['status']);
        $this->assertTrue($state['statement_generated']);
    }

    public function test_month_cannot_be_validated_twice(): void
    {
        DB::table('payroll_edition_validations')->insert(['payroll_period_id' => $this->period->id, 'ia_id' => 2, 'validated_at' => now()->subHour()]);
        $this->mock(PayrollCalculationService::class)->shouldNotReceive('calculate');
        $this->expectException(ConflictHttpException::class);
        $this->expectExceptionMessage('déjà validée');
        app(PayrollEditionService::class)->validate($this->actor(), $this->period->id, Request::create('/'));
    }

    public function test_validation_recalculates_ia_teachers_and_removes_stale_slips(): void
    {
        DB::table('enseignants')->insert([
            ['id' => 1, 'est_actif' => true, 'statut_enseignant_id' => 1, 'ia_id' => 2, 'date_prise_service' => '2025-01-01'],
            ['id' => 2, 'est_actif' => true, 'statut_enseignant_id' => 1, 'ia_id' => 2, 'date_prise_service' => '2027-01-01'], // pas encore en service
            ['id' => 3, 'est_actif' => true, 'statut_enseignant_id' => 1, 'ia_id' => 1, 'date_prise_service' => '2025-01-01'], // autre IA
            ['id' => 4, 'est_actif' => true, 'statut_enseignant_id' => 3, 'ia_id' => 2, 'date_prise_service' => '2025-01-01'], // fonctionnaire
        ]);
        DB::table('payroll_payslips')->insert([
            ['payroll_period_id' => $this->period->id, 'enseignant_id' => 2, 'ia_id' => 2, 'reference' => 'BS-STALE', 'net_amount' => 10],
            ['payroll_period_id' => $this->period->id, 'enseignant_id' => 3, 'ia_id' => 1, 'reference' => 'BS-OTHER', 'net_amount' => 20],
        ]);
        $this->mock(PayrollCalculationService::class)->shouldReceive('calculate')->once()
            ->withArgs(function ($period, $actor, $ids) {
                $this->assertSame([1], $ids);
                DB::table('payroll_payslips')->insert(['payroll_period_id' => $period->id, 'enseignant_id' => 1, 'ia_id' => 2, 'reference' => 'BS-NEW', 'net_amount' => 100]);
                return true;
            })->andReturn(new PayrollRun());

        $result = app(PayrollEditionService::class)->validate($this->actor(), $this->period->id, Request::create('/'));

        $this->assertSame('validee', $result['status']);
        $this->assertSame(['BS-STALE'], $result['removed']);
        $this->assertSame(1, $result['payslip_count']);
        $this->assertEquals(100, $result['total_net']);
        $this->assertSame(['BS-NEW', 'BS-OTHER'], DB::table('payroll_payslips')->orderBy('reference')->pluck('reference')->all());
    }

    public function test_paid_slip_blocks_validation_and_closed_period_cannot_be_edited(): void
    {
        DB::table('payroll_payslips')->insert(['payroll_period_id' => $this->period->id, 'enseignant_id' => 9, 'ia_id' => 2, 'reference' => 'BS-PAID', 'payment_status' => 'paid']);
        try {
            app(PayrollEditionService::class)->validate($this->actor(), $this->period->id, Request::create('/'));
            $this->fail('Paid slip ignored');
        } catch (ConflictHttpException $e) {
            $this->assertStringContainsString('BS-PAID', $e->getMessage());
        }
        $this->assertSame(0, DB::table('payroll_edition_validations')->count());

        $this->period->update(['status' => 'closed']);
        $this->expectException(ConflictHttpException::class);
        app(PayrollEditionService::class)->validate($this->actor(), $this->period->id, Request::create('/'));
    }
}
