<?php

namespace Tests\Feature;

use App\Models\PayrollPeriod;
use App\Services\PayrollPeriodCatalogue;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PayrollPeriodCatalogueTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Schema::create('periode_de_paies', function (Blueprint $table) {
            $table->id(); $table->string('code'); $table->string('libelle');
        });
        Schema::create('annee_academiques', function (Blueprint $table) {
            $table->id(); $table->date('date_debut'); $table->date('date_fin'); $table->boolean('est_cloturee')->default(false);
        });
        Schema::create('payroll_periods', function (Blueprint $table) {
            $table->id(); $table->string('code')->unique(); $table->string('label');
            $table->date('start_date'); $table->date('end_date'); $table->string('status'); $table->timestamps();
        });
        DB::table('annee_academiques')->insert(['date_debut' => '2026-10-01', 'date_fin' => '2027-09-30']);
    }

    public function test_only_crud_months_are_materialized_with_correct_academic_year_dates(): void
    {
        DB::table('periode_de_paies')->insert([
            ['code' => '10', 'libelle' => 'OCTOBRE'], ['code' => 'JUIN', 'libelle' => 'JUIN'],
        ]);
        $catalogue = app(PayrollPeriodCatalogue::class);
        $this->assertEqualsCanonicalizing(['2026-10', '2027-06'], $catalogue->synchronize()->all());
        $this->assertSame('OCTOBRE 2026', PayrollPeriod::where('code', '2026-10')->value('label'));
        $this->assertSame('2027-06-30', PayrollPeriod::where('code', '2027-06')->first()->end_date->format('Y-m-d'));
        $this->assertEquals([6, 10], array_column($catalogue->months(), 'value'));
        $catalogue->synchronize();
        $this->assertSame(2, PayrollPeriod::count());
    }

    public function test_existing_calculated_period_is_reused_without_rewriting_status_or_label(): void
    {
        DB::table('periode_de_paies')->insert(['code' => '2026-10', 'libelle' => 'OCTOBRE']);
        $period = PayrollPeriod::create(['code' => '2026-10', 'label' => 'Libellé historique', 'start_date' => '2026-10-01', 'end_date' => '2026-10-31', 'status' => 'calculated']);
        app(PayrollPeriodCatalogue::class)->synchronize();
        $this->assertSame($period->id, PayrollPeriod::first()->id);
        $this->assertSame('calculated', $period->fresh()->status);
        $this->assertSame('Libellé historique', $period->fresh()->label);
    }

    public function test_empty_or_unrecognizable_catalogue_does_not_invent_periods(): void
    {
        $catalogue = app(PayrollPeriodCatalogue::class);
        $this->assertSame([], $catalogue->synchronize()->all());
        DB::table('periode_de_paies')->insert(['code' => 'LIBRE', 'libelle' => 'Période spéciale']);
        $this->assertSame([], $catalogue->synchronize()->all());
        $this->assertSame(0, PayrollPeriod::count());
    }

    public function test_closed_academic_year_does_not_create_new_payroll_periods(): void
    {
        DB::table('annee_academiques')->update(['est_cloturee' => true]);
        DB::table('periode_de_paies')->insert(['code' => '10', 'libelle' => 'OCTOBRE']);
        $this->assertSame([], app(PayrollPeriodCatalogue::class)->synchronize()->all());
    }

    public function test_period_creation_action_and_form_are_removed(): void
    {
        $route = collect(app('router')->getRoutes()->getRoutes())->first(fn ($route) => $route->uri() === 'api/payroll/actions/{action}');
        $this->assertNotNull($route);
        $this->assertStringNotContainsString('create-period', $route->wheres['action']);
    }
}
