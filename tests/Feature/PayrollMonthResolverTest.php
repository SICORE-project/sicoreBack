<?php

namespace Tests\Feature;

use App\Services\PayrollMonthResolver;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class PayrollMonthResolverTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Schema::create('periode_de_paies', function (Blueprint $t) { $t->id(); $t->string('code'); $t->string('libelle'); });
        Schema::create('annee_academiques', function (Blueprint $t) { $t->id(); $t->date('date_debut'); $t->date('date_fin'); $t->boolean('est_active'); $t->boolean('est_cloturee'); });
        Schema::create('payroll_periods', function (Blueprint $t) { $t->id(); $t->string('code'); $t->date('start_date'); $t->date('end_date'); });
        DB::table('periode_de_paies')->insert([['code' => 'JAN', 'libelle' => 'Janvier'], ['code' => 'FEV', 'libelle' => 'Février'], ['code' => 'MAR', 'libelle' => 'Mars']]);
        DB::table('annee_academiques')->insert(['id' => 2, 'date_debut' => '2027-10-01', 'date_fin' => '2028-09-30', 'est_active' => false, 'est_cloturee' => true]);
        foreach (['2027-01', '2028-01', '2028-02'] as $code) {
            DB::table('payroll_periods')->insert(['code' => $code, 'start_date' => $code.'-01', 'end_date' => $code.'-28']);
        }
    }

    public function test_month_resolves_to_selected_academic_year_even_when_inactive_and_closed(): void
    {
        $this->assertSame('2028-01', app(PayrollMonthResolver::class)->resolve(1, 2)->code);
        $this->assertSame(3, DB::table('payroll_periods')->count());
    }

    public function test_catalogue_month_without_treatment_does_not_fall_back_or_create_payroll(): void
    {
        $this->assertNull(app(PayrollMonthResolver::class)->resolve(3, 2));
        $this->assertNull(app(PayrollMonthResolver::class)->resolve(1, null));
        $this->assertSame(3, DB::table('payroll_periods')->count());
    }

    public function test_month_removed_from_parametrage_cannot_be_consulted_by_new_month_filter(): void
    {
        DB::table('periode_de_paies')->where('code', 'JAN')->delete();
        $this->expectException(ValidationException::class);
        app(PayrollMonthResolver::class)->resolve(1, 2);
    }
}
