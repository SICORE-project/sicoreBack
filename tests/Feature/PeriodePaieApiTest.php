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
        // The lifecycle has its own atomicity/national calculation tests.
        $this->mock(\App\Services\PayrollPeriodLifecycle::class)->shouldReceive('open')->zeroOrMoreTimes()
            ->andReturn(new \App\Models\PayrollPeriod());
        $this->mock(\App\Services\PayrollPeriodAutoClosure::class)->shouldReceive('run')->zeroOrMoreTimes()->andReturn(['closed' => 0, 'failed' => 0]);
    }

    public function test_admin_peut_effectuer_le_crud_complet(): void
    {
        Sanctum::actingAs($this->admin());

        $create = $this->postJson('/api/periodes-paie', [
            'code' => ' paie-08 ',
            'libelle' => ' Août 2026 ',
            'date_debut' => '2026-08-01', 'date_fin' => '2026-08-31',
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
            'date_debut' => '2026-08-01', 'date_fin' => '2026-08-31',
        ])->assertCreated();

        $this->postJson('/api/periodes-paie', [
            'code' => 'paie-08',
            'libelle' => 'Doublon',
        ])->assertUnprocessable()->assertJsonValidationErrors('code');

        $this->postJson('/api/periodes-paie', ['libelle' => 'Sans code'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('code');
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
