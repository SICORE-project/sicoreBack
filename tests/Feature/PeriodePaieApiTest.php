<?php

namespace Tests\Feature;

use App\Models\admin\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PeriodePaieApiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(\Carbon\Carbon::parse('2026-08-01'));

        Schema::create('roles', function (Blueprint $table): void {
            $table->id();
            $table->string('nom');
            $table->string('slug')->unique();
            $table->timestamps();
        });
        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('nom');
            $table->string('prenom');
            $table->string('email')->unique();
            $table->string('password');
            $table->foreignId('role_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('periode_de_paies', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('libelle', 100);
            $table->timestamps();
        });
        Schema::create('payroll_periods', function (Blueprint $table): void {
            $table->id(); $table->string('code'); $table->string('label'); $table->string('status'); $table->date('start_date'); $table->date('end_date'); $table->dateTime('closed_at')->nullable();
        });
        Schema::table('periode_de_paies', function (Blueprint $table): void { $table->date('date_debut')->nullable(); $table->date('date_fin')->nullable(); $table->integer('payroll_period_id')->nullable(); });
        foreach (['heures_supplementaires', 'mise_a_jour_mc_ve', 'mise_a_jour_pc_vac'] as $table) {
            Schema::create($table, function (Blueprint $t): void { $t->id(); $t->integer('periode_paie_id'); });
        }
        // Registering a reusable month must never infer a year or calculate payroll.
        $this->mock(\App\Services\PayrollPeriodLifecycle::class)->shouldNotReceive('open');
        $this->mock(\App\Services\PayrollPeriodAutoClosure::class)->shouldReceive('run')->zeroOrMoreTimes()->andReturn(['closed' => 0, 'failed' => 0]);
    }

    public function test_admin_peut_effectuer_le_crud_complet(): void
    {
        Sanctum::actingAs($this->admin());

        $create = $this->postJson('/api/periodes-paie', [
            'code' => ' paie-08 ',
            'libelle' => ' Août 2026 ',
        ])->assertCreated()
            ->assertJsonPath('data.code', 'PAIE-08')
            ->assertJsonPath('data.libelle', 'Août 2026');
        $id = $create->json('data.id');

        $this->getJson('/api/periodes-paie?search=août')
            ->assertOk()
            ->assertJsonPath('data.data.0.id', $id);

        $this->getJson("/api/periodes-paie/{$id}")
            ->assertOk()
            ->assertJsonPath('data.code', 'PAIE-08');

        $this->patchJson("/api/periodes-paie/{$id}", [
            'code' => 'PAIE-08',
            'libelle' => 'Paie août 2026',
        ])->assertOk()
            ->assertJsonPath('data.libelle', 'Paie août 2026');

        $this->deleteJson("/api/periodes-paie/{$id}")->assertOk();
        $this->getJson("/api/periodes-paie/{$id}")->assertNotFound();
    }

    public function test_code_est_obligatoire_et_unique(): void
    {
        Sanctum::actingAs($this->admin());

        $this->postJson('/api/periodes-paie', [
            'code' => 'PAIE-08',
            'libelle' => 'Août 2026',
        ])->assertCreated();

        $this->postJson('/api/periodes-paie', [
            'code' => 'paie-08',
            'libelle' => 'Doublon',
        ])->assertUnprocessable()->assertJsonValidationErrors('code');

        $this->postJson('/api/periodes-paie', ['libelle' => 'Sans code'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('code');
    }

    public function test_twelve_reusable_months_can_be_registered_without_academic_year_or_teachers(): void
    {
        Sanctum::actingAs($this->admin());
        $months = ['Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin', 'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre'];
        foreach ($months as $index => $month) {
            $this->postJson('/api/periodes-paie', ['code' => sprintf('%02d', $index + 1), 'libelle' => $month])
                ->assertCreated()->assertJsonPath('data.libelle', $month)
                ->assertJsonPath('data.payroll_period_id', null);
        }
        $this->assertSame(12, DB::table('periode_de_paies')->count());
        $this->assertSame(0, DB::table('payroll_periods')->count());
        $this->postJson('/api/periodes-paie', ['code' => 'AVR', 'libelle' => 'AVRIL'])
            ->assertUnprocessable()->assertJsonValidationErrors('libelle');
    }

    public function test_april_does_not_depend_on_multiple_closed_or_inactive_academic_years(): void
    {
        Sanctum::actingAs($this->admin());
        Schema::create('annee_academiques', function (Blueprint $t) {
            $t->id(); $t->date('date_debut'); $t->date('date_fin'); $t->boolean('est_active'); $t->boolean('est_cloturee');
        });
        DB::table('annee_academiques')->insert([
            ['date_debut' => '2026-10-01', 'date_fin' => '2027-09-30', 'est_active' => false, 'est_cloturee' => true],
            ['date_debut' => '2027-10-01', 'date_fin' => '2028-09-30', 'est_active' => true, 'est_cloturee' => false],
        ]);
        $this->postJson('/api/periodes-paie', ['code' => 'AVR', 'libelle' => 'Avril'])
            ->assertCreated()->assertJsonPath('data.code', 'AVR')->assertJsonPath('data.libelle', 'Avril');
        $this->assertSame(0, DB::table('payroll_periods')->count());
    }

    private function admin(): User
    {
        $roleId = DB::table('roles')->insertGetId([
            'nom' => 'Super admin',
            'slug' => 'super_admin',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return User::query()->create([
            'nom' => 'Ndiaye',
            'prenom' => 'Test',
            'email' => 'periode@example.test',
            'password' => 'secret',
            'role_id' => $roleId,
        ]);
    }
}
