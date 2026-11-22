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
        Schema::create('periode_de_paies', function (Blueprint $t) { $t->id(); $t->string('code'); $t->string('libelle'); $t->unsignedTinyInteger('numero_mois')->nullable(); });
        Schema::create('annee_academiques', function (Blueprint $t) { $t->id(); $t->date('date_debut'); $t->date('date_fin'); $t->boolean('est_active'); $t->boolean('est_cloturee'); });
        Schema::create('payroll_periods', function (Blueprint $t) { $t->id(); $t->string('code'); $t->date('start_date'); $t->date('end_date'); $t->unsignedBigInteger('annee_academique_id')->nullable(); });
        DB::table('periode_de_paies')->insert([['code' => 'JAN', 'libelle' => 'Janvier', 'numero_mois' => 1], ['code' => 'FEV', 'libelle' => 'Février', 'numero_mois' => 2], ['code' => 'MAR', 'libelle' => 'Mars', 'numero_mois' => 3]]);
        DB::table('annee_academiques')->insert(['id' => 2, 'date_debut' => '2027-10-01', 'date_fin' => '2028-09-30', 'est_active' => false, 'est_cloturee' => true]);
        foreach (['2027-01' => null, '2028-01' => 2, '2028-02' => 2] as $code => $year) {
            DB::table('payroll_periods')->insert(['code' => $code, 'start_date' => $code.'-01', 'end_date' => $code.'-28', 'annee_academique_id' => $year]);
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

    public function test_link_is_the_foreign_key_not_the_dates(): void
    {
        // Période dont les dates tombent dans l'année 2 mais rattachée à aucune année.
        DB::table('payroll_periods')->where('code', '2028-01')->update(['annee_academique_id' => null]);
        $this->assertNull(app(PayrollMonthResolver::class)->resolve(1, 2));
    }

    public function test_month_start_uses_overlap_rule_for_partial_months(): void
    {
        // L'année commence le 21 octobre et finit le 22 juin : octobre et juin lui appartiennent, juillet non.
        $year = (object) ['date_debut' => '2026-10-21', 'date_fin' => '2027-06-22'];
        $resolver = app(PayrollMonthResolver::class);
        $this->assertSame('2026-10-01', $resolver->monthStart(10, $year)->toDateString());
        $this->assertSame('2027-06-01', $resolver->monthStart(6, $year)->toDateString());
        $this->expectException(ValidationException::class);
        $resolver->monthStart(7, $year);
    }

    public function test_month_removed_from_parametrage_cannot_be_consulted_by_new_month_filter(): void
    {
        DB::table('periode_de_paies')->where('code', 'JAN')->delete();
        $this->expectException(ValidationException::class);
        app(PayrollMonthResolver::class)->resolve(1, 2);
    }
}
