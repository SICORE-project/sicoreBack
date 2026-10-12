<?php

namespace Tests\Feature;

use App\Models\Admin\Permission;
use App\Models\Admin\Role;
use App\Models\Admin\User;
use App\Models\Parametrage\LieuService;
use App\Models\Personnel\Enseignant;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RoleDashboardTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        foreach (['2026_07_04_000000_create_users_table', '2026_07_27_235253_create_roles_table', '2026_07_07_120005_create_enseignants_table', '2026_07_07_120015_create_delegation_credits_table'] as $migration) {
            (require database_path('migrations/'.$migration.'.php'))->up();
        }
        Schema::create('corps_enseignant', function (Blueprint $t) { $t->id(); $t->string('libelle'); });
        Schema::create('type_roles', function (Blueprint $t) { $t->id(); $t->string('code'); });
        Schema::table('roles', fn (Blueprint $t) => $t->unsignedBigInteger('type_role_id')->nullable());
        Schema::create('permissions', function (Blueprint $t) { $t->id(); $t->string('slug'); $t->boolean('est_actif')->default(true); $t->timestamps(); });
        Schema::create('role_permission', function (Blueprint $t) { $t->unsignedBigInteger('role_id'); $t->unsignedBigInteger('permission_id'); });
        Schema::create('lieu_de_services', function (Blueprint $t) {
            $t->id(); $t->string('libelle'); $t->string('type'); $t->boolean('est_actif')->default(true);
            $t->unsignedBigInteger('ia_id')->nullable(); $t->unsignedBigInteger('ief_id')->nullable(); $t->timestamps(); $t->softDeletes();
        });
        Schema::create('payroll_periods', function (Blueprint $t) { $t->id(); $t->string('code'); $t->date('start_date'); });
        Schema::create('payroll_runs', function (Blueprint $t) { $t->id(); $t->string('status'); });
        Schema::create('payroll_payslips', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('enseignant_id'); $t->unsignedBigInteger('payroll_period_id');
            $t->unsignedBigInteger('payroll_run_id'); $t->string('reference'); $t->string('payment_status'); $t->decimal('net_amount', 14, 2);
        });
        DB::table('payroll_periods')->insert(['id' => 1, 'code' => '2026-09', 'start_date' => '2026-09-01']);
        DB::table('payroll_runs')->insert([['id' => 1, 'status' => 'validated'], ['id' => 2, 'status' => 'calculated']]);
        foreach ([[1, 10, 100, 'DossierA'], [2, 10, 101, 'DossierB'], [3, 20, 200, 'DossierSecret']] as [$id, $ia, $ief, $name]) {
            Enseignant::create(['id' => $id, 'matricule' => 'MAT'.$id, 'nom' => $name, 'prenom' => 'Test', 'ia_id' => $ia, 'ief_id' => $ief]);
        }
        // IDs are assigned sequentially by the model's guarded primary key.
        foreach ([1, 2, 3] as $id) {
            DB::table('payroll_payslips')->insert(['enseignant_id' => $id, 'payroll_period_id' => 1, 'payroll_run_id' => $id === 2 ? 2 : 1,
                'reference' => 'BUL'.$id, 'payment_status' => 'pending', 'net_amount' => $id * 100]);
            DB::table('delegation_credits')->insert(['enseignant_id' => $id, 'reference_lettre' => 'DEL'.$id, 'annee_academique' => '2026', 'date_enregistrement' => '2026-09-01', 'montant_alouer' => $id * 1000]);
        }
    }

    public function test_super_admin_charts_distinguish_pending_teacher_activation(): void
    {
        $admin = $this->login('super_admin');
        User::create(['nom' => 'Enseignant', 'prenom' => 'Test', 'email' => 'pending@example.test',
            'password' => 'password123', 'statut' => 'actif', 'role_id' => $admin->role_id, 'enseignant_id' => 1]);
        DB::table('corps_enseignant')->insert([
            ['id' => 1, 'libelle' => 'Corps A'], ['id' => 2, 'libelle' => 'Corps B'], ['id' => 3, 'libelle' => 'Corps C'],
        ]);
        Enseignant::where('id', 1)->update(['corps_id' => 1]);
        Enseignant::whereIn('id', [2, 3])->update(['corps_id' => 2]);
        $this->getJson('/api/dashboard')->assertOk()
            ->assertJsonPath('data.analytics.distributions.1.items.0.value', 1)
            ->assertJsonPath('data.analytics.distributions.1.items.1.value', 0)
            ->assertJsonPath('data.analytics.distributions.2.items.0.value', 1)
            ->assertJsonPath('data.analytics.distributions.2.items.1.value', 2)
            ->assertJsonPath('data.analytics.distributions.2.items.2.value', 0)
            ->assertJsonPath('data.layout', 'super-admin')
            ->assertJsonCount(12, 'data.analytics.history')
            ->assertJsonPath('data.analytics.distributions.0.items.0.value', 1)
            ->assertJsonPath('data.analytics.distributions.0.items.1.value', 1)
            ->assertJsonPath('data.analytics.distributions.3.items.0.value', 3);
    }

    public function test_successful_login_records_last_connection_for_all_profiles(): void
    {
        (require database_path('migrations/2026_07_30_135016_create_personal_access_tokens_table.php'))->up();
        foreach (['super_admin', 'enseignant'] as $slug) {
            $user = $this->login($slug);
            $this->assertNull($user->derniere_connexion);
            $this->travelTo(now()->startOfSecond());
            $service = app(\App\Services\Auth\AuthService::class);
            $response = $service->login(['email' => $user->email, 'password' => 'password123']);
            $this->assertSame(200, $response->getStatusCode());
            $this->assertTrue($user->fresh()->derniere_connexion->equalTo(now()));
            $recorded = $user->fresh()->derniere_connexion;
            $this->travel(5)->minutes();
            try {
                $service->login(['email' => $user->email, 'password' => 'wrong-password']);
                $this->fail('Invalid password must be rejected.');
            } catch (\Illuminate\Validation\ValidationException $exception) {
                $this->assertTrue($user->fresh()->derniere_connexion->equalTo($recorded));
            }
            $service->login(['email' => $user->email, 'password' => 'password123']);
            $this->assertTrue($user->fresh()->derniere_connexion->equalTo(now()));
            $this->travelBack();
        }
    }

    private function login(string $slug, array $permissions = [], ?string $type = null, ?int $teacher = null): User
    {
        $role = Role::create(['nom' => $slug, 'slug' => $slug, 'est_actif' => true]);
        foreach ($permissions as $permission) $role->permissions()->attach(Permission::firstOrCreate(['slug' => $permission])->id);
        $structure = $type ? LieuService::create(['libelle' => 'Structure '.$type, 'type' => $type, 'ia_id' => 10, 'ief_id' => 100, 'est_actif' => true]) : null;
        $user = User::create(['nom' => 'Nom', 'prenom' => 'Prénom', 'email' => $slug.'@example.test', 'password' => 'password123',
            'statut' => 'actif', 'role_id' => $role->id, 'lieu_service_id' => $structure?->id, 'enseignant_id' => $teacher]);
        Sanctum::actingAs($user, ['*']);
        return $user;
    }

    public function test_ia_cannot_override_its_scope_with_query_parameters(): void
    {
        $this->login('gestionnaire_ia', ['enseignants.read'], 'IA');
        $response = $this->getJson('/api/dashboard?ia_id=20&ief_id=200&role=super_admin')->assertOk()->assertJsonPath('data.cards.0.value', 2);
        $response->assertSee('DossierA')->assertSee('DossierB')->assertDontSee('DossierSecret')->assertDontSee('Comptes utilisateurs');
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
    }

    public function test_ief_only_sees_its_own_teachers(): void
    {
        $this->login('gestionnaire_ief', ['enseignants.read'], 'IEF');
        $this->getJson('/api/dashboard')->assertOk()->assertJsonPath('data.cards.0.value', 1)->assertDontSee('DossierB')->assertDontSee('DossierSecret');
    }

    public function test_missing_or_inactive_structure_never_falls_back_to_global_data(): void
    {
        $user = $this->login('gestionnaire_ia', ['enseignants.read']);
        $this->getJson('/api/dashboard')->assertOk()->assertJsonPath('data.cards', [])->assertJsonPath('data.sections', []);
        $structure = LieuService::create(['libelle' => 'Inactive', 'type' => 'IA', 'ia_id' => 10, 'est_actif' => false]);
        $user->update(['lieu_service_id' => $structure->id]); $user->unsetRelation('lieuService');
        $this->getJson('/api/dashboard')->assertOk()->assertJsonPath('data.cards', []);
    }

    public function test_teacher_only_sees_own_validated_payslips_even_with_extra_permissions(): void
    {
        $this->login('enseignant', ['paie.bulletins.read', 'enseignants.read', 'administration.users.read'], null, 1);
        DB::table('payroll_payslips')->insert(['enseignant_id' => 1, 'payroll_period_id' => 1, 'payroll_run_id' => 2, 'reference' => 'BROUILLON', 'payment_status' => 'pending', 'net_amount' => 999]);
        $this->getJson('/api/dashboard?enseignant_id=3')->assertOk()->assertJsonPath('data.cards.0.value', 1)
            ->assertJsonPath('data.layout', 'teacher')->assertJsonCount(4, 'data.cards')
            ->assertJsonPath('data.charts.net_history.0.amount', 100)
            ->assertJsonCount(1, 'data.charts.net_history')
            ->assertJsonPath('data.charts.payments.1.value', 1)
            ->assertSee('BUL1')->assertDontSee('BROUILLON')->assertDontSee('BUL2')->assertDontSee('BUL3')->assertDontSee('DossierSecret')->assertJsonPath('data.actions', []);
    }

    public function test_teacher_charts_follow_period_dates_and_keep_zero_payment_counts(): void
    {
        $this->login('enseignant', ['paie.bulletins.read'], null, 1);
        DB::table('payroll_periods')->insert(['id' => 2, 'code' => '2026-08', 'start_date' => '2026-08-01']);
        DB::table('payroll_payslips')->insert(['enseignant_id' => 1, 'payroll_period_id' => 2, 'payroll_run_id' => 1, 'reference' => 'OLDER', 'payment_status' => 'paid', 'net_amount' => 80]);
        $this->getJson('/api/dashboard')->assertOk()
            ->assertJsonPath('data.charts.net_history.0.period', '2026-08')
            ->assertJsonPath('data.charts.net_history.1.period', '2026-09')
            ->assertJsonPath('data.charts.payments.0.value', 1)
            ->assertJsonPath('data.charts.payments.2.value', 0)
            ->assertJsonPath('data.cards.3.value', '100.00');
    }

    public function test_teacher_without_payslip_permission_gets_no_charts(): void
    {
        $this->login('enseignant', [], null, 1);
        $this->getJson('/api/dashboard')->assertOk()->assertJsonPath('data.charts', null)->assertJsonPath('data.cards', []);
    }

    public function test_teacher_without_link_does_not_see_any_collective_data(): void
    {
        $this->login('enseignant', ['paie.bulletins.read']);
        $this->getJson('/api/dashboard')->assertOk()->assertJsonPath('data.cards', [])->assertJsonPath('data.sections', []);
    }

    public function test_payroll_statistics_are_scoped_and_do_not_expose_global_shortcuts_to_regional_accounts(): void
    {
        $this->login('gestionnaire_paie', ['paie.bulletins.read'], 'IEF');
        $this->getJson('/api/dashboard')->assertOk()->assertJsonPath('data.cards.0.value', 1)->assertJsonPath('data.cards.3.value', 100)
            ->assertJsonPath('data.actions', [])->assertDontSee('Comptes utilisateurs');
    }

    public function test_budget_uses_real_scoped_credits(): void
    {
        $this->login('gestionnaire_budget', ['budget.read'], 'IA');
        $this->getJson('/api/dashboard')->assertOk()->assertJsonPath('data.cards.0.value', 2)->assertJsonPath('data.cards.1.value', 3000)
            ->assertSee('DEL1')->assertSee('DEL2')->assertDontSee('DEL3')->assertDontSee('Comptes utilisateurs');
    }

    public function test_super_admin_gets_global_metrics(): void
    {
        $this->login('super_admin');
        $response = $this->getJson('/api/dashboard')->assertOk();
        $cards = collect($response->json('data.cards'))->keyBy('label');
        $this->assertEquals(3, $cards['Total des enseignants']['value']);
        $this->assertEquals(6000, $cards['Crédits alloués']['value']);
        $this->assertContains('utilisateurs.index', array_column($response->json('data.actions'), 'route'));
    }

    public function test_admin_and_national_hr_respect_permissions(): void
    {
        $this->login('admin', ['administration.users.read']);
        $this->getJson('/api/dashboard')->assertOk()->assertJsonCount(3, 'data.cards')->assertJsonCount(1, 'data.actions')->assertDontSee('DossierA');
    }

    public function test_national_hr_has_hr_data_without_administration_or_payroll(): void
    {
        $this->login('drh', ['enseignants.read'], 'DRH');
        $this->getJson('/api/dashboard')->assertOk()->assertJsonPath('data.cards.0.value', 3)->assertDontSee('BUL1')->assertDontSee('Comptes utilisateurs');
    }

    public function test_inactive_permission_does_not_grant_access(): void
    {
        $this->login('consultant', ['enseignants.read'], 'IA');
        Permission::query()->update(['est_actif' => false]);
        $this->getJson('/api/dashboard')->assertOk()->assertJsonPath('data.cards', [])->assertJsonPath('data.sections', []);
    }

    public function test_inactive_account_is_rejected(): void
    {
        $user = $this->login('admin', ['administration.users.read']);
        $user->update(['statut' => 'inactif']);
        $this->getJson('/api/dashboard')->assertForbidden();
    }

    public function test_inactive_role_is_rejected(): void
    {
        $user = $this->login('admin', ['administration.users.read']);
        $user->role->update(['est_actif' => false]);
        $this->getJson('/api/dashboard')->assertForbidden();
    }

    public function test_wrong_territorial_structure_is_not_treated_as_national_access(): void
    {
        $this->login('gestionnaire_ia', ['enseignants.read'], 'DRH');
        $this->getJson('/api/dashboard')->assertOk()->assertJsonPath('data.cards', [])->assertJsonPath('data.sections', []);
    }

    public function test_national_payroll_shortcuts_require_payroll_token_ability(): void
    {
        $user = $this->login('gestionnaire_paie', ['paie.bulletins.read'], 'DAGE');
        $this->getJson('/api/dashboard')->assertOk()->assertJsonCount(2, 'data.actions');
        Sanctum::actingAs($user, ['unrelated:read']);
        $this->getJson('/api/dashboard')->assertOk()->assertJsonPath('data.actions', []);
    }

    public function test_unauthenticated_request_is_rejected(): void
    {
        $this->getJson('/api/dashboard')->assertUnauthorized();
    }
}
