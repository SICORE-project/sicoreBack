<?php

namespace Tests\Feature;

use App\Models\Admin\User;
use App\Services\Administration\UserService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class IaAccessTest extends TestCase
{
    private User $manager;

    protected function setUp(): void
    {
        parent::setUp();
        foreach (['roles' => ['nom', 'slug'], 'permissions' => ['nom', 'slug'], 'ias' => ['libelle'], 'iefs' => ['libelle']] as $name => $columns) {
            Schema::create($name, function (Blueprint $t) use ($columns, $name) {
                $t->id();
                foreach ($columns as $column) {
                    $t->string($column);
                }
                if ($name === 'iefs') {
                    $t->integer('ia_id');
                }
                if (in_array($name, ['ias', 'iefs'])) {
                    $t->string('code')->nullable();
                }
                if ($name === 'ias') {
                    $t->integer('region_id')->nullable();
                }
                $t->timestamps();
                $t->softDeletes();
            });
        }
        Schema::create('regions', function (Blueprint $t) {
            $t->id();
            $t->string('nom');
        });
        Schema::create('role_permission', function (Blueprint $t) {
            $t->integer('role_id');
            $t->integer('permission_id');
        });
        Schema::create('users', function (Blueprint $t) {
            $t->id();
            foreach (['nom', 'prenom', 'email', 'password'] as $c) {
                $t->string($c);
            }
            foreach (['role_id', 'ia_id', 'ief_id', 'lieu_service_id'] as $c) {
                $t->integer($c)->nullable();
            }
            $t->timestamps();
            $t->softDeletes();
        });
        Schema::create('lieu_de_services', function (Blueprint $t) {
            $t->id();
            $t->string('libelle');
            $t->string('type');
            $t->integer('ia_id');
            $t->integer('ief_id')->nullable();
            $t->boolean('est_actif')->default(true);
            $t->timestamps();
            $t->softDeletes();
        });
        Schema::create('enseignants', function (Blueprint $t) {
            $t->id();
            foreach (['nom', 'prenom', 'matricule', 'statut', 'type_engagement'] as $c) {
                $t->string($c);
            }
            foreach (['ia_id', 'ief_id', 'lieu_service_id'] as $c) {
                $t->integer($c);
            }
            $t->boolean('est_actif')->default(true);
            $t->date('date_prise_service')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });
        Schema::create('payroll_periods', function (Blueprint $t) {
            $t->id();
            $t->string('code');
            $t->date('start_date');
        });
        Schema::create('payroll_payslips', function (Blueprint $t) {
            $t->id();
            $t->integer('enseignant_id');
            $t->integer('payroll_period_id');
            foreach (['gross_amount', 'net_amount', 'deduction_amount'] as $c) {
                $t->decimal($c)->default(0);
            }
            $t->string('payment_status');
            $t->timestamps();
        });
        (require database_path('migrations/2026_09_21_000001_create_personnel_audit_logs_table.php'))->up();
        DB::table('roles')->insert(['id' => 1, 'nom' => 'Gestionnaire IA', 'slug' => 'gestionnaire_ia']);
        foreach (['enseignants.read', 'paie.bulletins.read', 'paie.bulletins.export', 'paie.masse_salariale.read'] as $index => $slug) {
            DB::table('permissions')->insert(['id' => $index + 1, 'nom' => $slug, 'slug' => $slug]);
            DB::table('role_permission')->insert(['role_id' => 1, 'permission_id' => $index + 1]);
        }
        foreach ([1 => 'Dakar', 2 => 'Thiès'] as $id => $name) {
            DB::table('ias')->insert(['id' => $id, 'libelle' => $name]);
            DB::table('iefs')->insert(['id' => $id, 'libelle' => 'IEF '.$name, 'ia_id' => $id]);
            DB::table('lieu_de_services')->insert(['id' => $id, 'libelle' => 'IA '.$name, 'type' => 'IA', 'ia_id' => $id, 'ief_id' => $id]);
        }
        foreach ([1 => 1, 2 => 2, 3 => 1] as $id => $ia) {
            DB::table('enseignants')->insert(['id' => $id, 'nom' => $ia === 1 ? 'Local' : 'HorsPerimetre', 'prenom' => 'Agent',
                'matricule' => 'MAT'.$id, 'ia_id' => $ia, 'ief_id' => $ia, 'lieu_service_id' => $ia,
                'statut' => 'en_activite', 'type_engagement' => $id === 3 ? 'contractuel' : 'fonctionnaire']);
        }
        DB::table('payroll_periods')->insert(['id' => 1, 'code' => '2026-09', 'start_date' => '2026-09-01']);
        foreach ([1, 2] as $id) {
            DB::table('payroll_payslips')->insert(['id' => $id, 'enseignant_id' => $id, 'payroll_period_id' => 1,
                'gross_amount' => $id * 100, 'net_amount' => $id * 90, 'payment_status' => 'paid', 'updated_at' => now()]);
        }
        $this->manager = User::create(['nom' => 'Fall', 'prenom' => 'Fatou', 'email' => 'ia@example.test', 'password' => 'secret123',
            'role_id' => 1, 'ia_id' => 1, 'lieu_service_id' => 1]);
        Sanctum::actingAs($this->manager);
    }

    public function test_dashboard_counts_only_own_ia(): void
    {
        $this->getJson('/api/ia/dashboard')->assertOk()->assertJsonPath('data.ia.libelle', 'Dakar')
            ->assertJsonPath('data.indicateurs.agents', 2)->assertJsonPath('data.indicateurs.fonctionnaires', 1)
            ->assertJsonPath('data.indicateurs.non_fonctionnaires', 1)->assertJsonPath('data.indicateurs.bulletins_generes', 1)
            ->assertJsonPath('data.indicateurs.bulletins_restants', 1)->assertJsonPath('data.indicateurs.masse_salariale', 100)
            ->assertJsonCount(1, 'data.iefs')->assertJsonCount(1, 'data.dernieres_operations')->assertDontSee('HorsPerimetre');
    }

    public function test_shared_payroll_reports_keep_full_salary_statement_and_scope_all_data(): void
    {
        Schema::table('users', fn (Blueprint $t) => $t->integer('enseignant_id')->nullable());
        Schema::table('payroll_periods', function (Blueprint $t) {
            $t->date('end_date')->default('2026-09-30');
            $t->string('label')->default('Septembre 2026');
            $t->string('status')->default('calculated');
            $t->integer('version')->default(1);
        });
        Schema::table('payroll_payslips', fn (Blueprint $t) => $t->string('reference')->default('BULLETIN'));
        Schema::create('corps_enseignant', function (Blueprint $t) { $t->id(); $t->string('code'); $t->string('libelle'); });
        Schema::create('annee_academiques', function (Blueprint $t) {
            $t->id(); $t->string('libelle'); $t->date('date_debut'); $t->date('date_fin');
        });
        foreach (['payroll_attendances', 'payroll_elements'] as $table) {
            Schema::create($table, function (Blueprint $t) {
                $t->id(); $t->integer('enseignant_id'); $t->integer('payroll_period_id');
                $t->string('code')->nullable(); $t->string('status')->nullable();
                $t->boolean('is_exempt')->default(false); $t->decimal('amount')->default(0);
                $t->timestamps();
            });
        }
        Schema::create('payroll_payslip_lines', function (Blueprint $t) {
            $t->id(); $t->integer('payroll_payslip_id'); $t->string('code'); $t->string('label');
            $t->string('category'); $t->string('source')->nullable(); $t->decimal('amount');
        });
        DB::table('permissions')->insert(['id' => 20, 'nom' => 'Salaires', 'slug' => 'paie.etat_salaires.read']);
        DB::table('role_permission')->insert(['role_id' => 1, 'permission_id' => 20]);
        $page = $this->getJson('/api/ia/payroll/pages/paie-etat-salaires?period_id=1&with_signature=1')
            ->assertOk()->assertJsonPath('data.scope_ia_id', 1)
            ->assertJsonPath('data.salary_statement.with_signature', true)
            ->assertJsonCount(1, 'data.salary_statement.rows')
            ->assertJsonCount(1, 'data.salary_statement.filter_options.ias')
            ->assertJsonCount(1, 'data.salary_statement.filter_options.iefs')
            ->assertDontSee('HorsPerimetre')->json('data');
        $this->assertCount(4, $page['stats']);
        $this->assertGreaterThan(10, count($page['salary_statement']['columns']));
        $this->assertEquals('100 FCFA', $page['stats'][1]['value']);
        $this->getJson('/api/ia/payroll/pages/paie-bulletins?period_id=1')->assertOk()
            ->assertJsonCount(1, 'data.rows')->assertDontSee('mark-paid')->assertDontSee('HorsPerimetre');
        $this->getJson('/api/ia/payroll/pages/paie-etat-salaires?ia_id=2')->assertForbidden();
        $this->getJson('/api/ia/payroll/pages/paie-etat-salaires?ief_id=2')->assertForbidden();
        $this->getJson('/api/ia/payroll/pages/paie-cotisations-sociales')->assertForbidden();
        foreach (['paie.cotisations.read', 'paie.recap_banque.read', 'paie.effectifs_ief.read', 'paie.sommes_percues.read'] as $permission) {
            $id = DB::table('permissions')->insertGetId(['nom' => $permission, 'slug' => $permission]);
            DB::table('role_permission')->insert(['role_id' => 1, 'permission_id' => $id]);
        }
        $catalog = $this->getJson('/api/ia/payroll/pages/paie-travaux-periodiques?period_id=1')
            ->assertOk()->assertJsonCount(22, 'data.report_catalog')->assertJsonPath('data.stats.0.value', 22)->json('data.report_catalog');
        foreach ($catalog as $report) {
            $this->getJson('/api/ia/payroll/pages/'.$report['slug'].'?period_id=1')->assertOk()->assertDontSee('HorsPerimetre');
        }
        $this->getJson('/api/ia/payroll/payslips/2')->assertForbidden();
        $this->get('/api/ia/payroll/pages/paie-etat-salaires/export?period_id=1')->assertOk()->assertDontSee('HorsPerimetre');
        $national = app(\App\Services\PayrollPageService::class)->page('paie-etat-salaires', 1);
        $this->assertCount(2, $national['salary_statement']['rows']);
    }

    public function test_lists_references_and_export_are_scoped_and_audited(): void
    {
        $this->getJson('/api/ia/enseignants')->assertOk()->assertJsonPath('total', 2)->assertDontSee('HorsPerimetre');
        $this->getJson('/api/ia/enseignants?engagement=non_fonctionnaires')->assertOk()
            ->assertJsonPath('total', 1)->assertJsonPath('data.0.type_engagement', 'contractuel');
        $this->getJson('/api/ia/referentiels')->assertOk()->assertJsonCount(1, 'data.iefs')->assertJsonCount(1, 'data.etablissements');
        $this->getJson('/api/ia/paie')->assertOk()->assertJsonPath('bulletins.total', 1)->assertDontSee('HorsPerimetre');
        $csv = $this->get('/api/ia/paie/export')->assertOk()->streamedContent();
        $this->assertStringContainsString('MAT1', $csv);
        $this->assertStringNotContainsString('MAT2', $csv);
        $this->assertDatabaseHas('personnel_audit_logs', ['user_id' => $this->manager->id, 'route' => 'api/ia/paie/export', 'action' => 'GET']);
    }

    public function test_cross_ia_parameters_details_and_national_routes_are_forbidden(): void
    {
        foreach (['ia/dashboard?ia_id=2', 'ia/enseignants?ia_id[]=1', 'ia/enseignants?ief_id=2', 'ia/enseignants?lieu_service_id=2',
            'ia/enseignants/2', 'ia/paie/2', 'ia/paie/export?ia_id=2', 'admin/users', 'admin/personnel/enseignants'] as $path) {
            $this->getJson('/api/'.$path)->assertForbidden();
        }
        $this->assertDatabaseHas('personnel_audit_logs', ['action' => 'acces_refuse', 'route' => 'api/ia/enseignants/2']);
        $this->getJson('/api/ia/enseignants/1')->assertOk();
        $this->getJson('/api/ia/paie/1')->assertOk();
    }

    public function test_missing_or_inconsistent_ia_is_denied(): void
    {
        $this->manager->ia_id = null;
        $this->getJson('/api/ia/dashboard')->assertForbidden();
        $this->manager->ia_id = 2;
        $this->getJson('/api/ia/dashboard')->assertForbidden();
    }

    public function test_permissions_gate_payroll_export_and_salary_mass(): void
    {
        DB::table('role_permission')->where('permission_id', 4)->delete();
        $this->getJson('/api/ia/dashboard')->assertOk()->assertJsonMissingPath('data.indicateurs.masse_salariale');
        DB::table('role_permission')->where('permission_id', 3)->delete();
        $this->getJson('/api/ia/paie/export')->assertForbidden();
        DB::table('role_permission')->where('permission_id', 2)->delete();
        $this->getJson('/api/ia/paie')->assertForbidden();
        $this->getJson('/api/ia/dashboard')->assertOk()->assertJsonMissingPath('data.periode');
    }

    public function test_user_creation_derives_ia_from_structure(): void
    {
        $user = app(UserService::class)->create(['nom' => 'Ba', 'prenom' => 'Awa', 'email' => 'new@example.test',
            'password' => 'secret123', 'role_id' => 1, 'lieu_service_id' => 1]);
        $this->assertEquals(1, $user->ia_id);
        $this->assertNull($user->ief_id);
    }

    public function test_user_creation_without_ia_structure_is_rejected(): void
    {
        $this->expectException(ValidationException::class);
        app(UserService::class)->create(['role_id' => 1, 'password' => 'secret123']);
    }

    public function test_ia_cannot_be_removed_or_conflict_with_structure(): void
    {
        $this->expectException(ValidationException::class);
        app(UserService::class)->update($this->manager, ['ia_id' => 2]);
    }

    public function test_login_rejects_unassigned_manager_before_issuing_token(): void
    {
        $this->manager->update(['ia_id' => null]);
        $this->postJson('/api/login', ['email' => 'ia@example.test', 'password' => 'secret123'])->assertForbidden();
    }

    public function test_valid_login_returns_ia_and_dashboard_destination(): void
    {
        Schema::create('personal_access_tokens', function (Blueprint $t) {
            $t->id();
            $t->morphs('tokenable');
            $t->string('name');
            $t->string('token', 64)->unique();
            $t->text('abilities')->nullable();
            $t->timestamp('last_used_at')->nullable();
            $t->timestamp('expires_at')->nullable();
            $t->timestamps();
        });
        $this->postJson('/api/login', ['email' => 'ia@example.test', 'password' => 'secret123'])->assertOk()
            ->assertJsonPath('redirect_to', '/dashboard')->assertJsonPath('user.ia_id', 1)
            ->assertJsonPath('user.ia.libelle', 'Dakar')->assertJsonStructure(['access_token']);
    }

    public function test_optional_payroll_permissions_are_registered_without_automatic_grants(): void
    {
        Schema::table('permissions', function (Blueprint $t) {
            foreach (['groupe', 'module', 'action'] as $column) {
                $t->string($column)->nullable();
            }
            $t->boolean('est_actif')->default(true);
        });
        DB::table('role_permission')->whereIn('permission_id', [3, 4])->delete();
        DB::table('permissions')->whereIn('id', [3, 4])->delete();
        $migration = require database_path('migrations/2026_09_21_030000_register_ia_optional_payroll_permissions.php');
        $migration->up();
        $migration->up();
        $this->assertEquals(1, DB::table('permissions')->where('slug', 'paie.masse_salariale.read')->count());
        $this->assertEquals(1, DB::table('permissions')->where('slug', 'paie.bulletins.export')->count());
        $this->assertDatabaseCount('role_permission', 2);
    }

    public function test_mandatory_ia_cannot_be_revoked(): void
    {
        $this->expectException(ValidationException::class);
        app(UserService::class)->revokeUserFromIa($this->manager->id);
    }

    public function test_payroll_reports_and_indicators_exclude_other_ias(): void
    {
        foreach (['sommes_percues', 'etat_salaires', 'cotisations', 'effectifs_ief', 'recap_banque'] as $module) {
            $id = DB::table('permissions')->insertGetId(['nom' => $module, 'slug' => 'paie.'.$module.'.read']);
            DB::table('role_permission')->insert(['role_id' => 1, 'permission_id' => $id]);
        }
        Schema::create('payroll_payslip_lines', function (Blueprint $t) {
            $t->id();
            $t->integer('payroll_payslip_id');
            $t->string('code');
            $t->string('label');
            $t->string('category');
            $t->decimal('amount');
        });
        Schema::create('comptes_bancaires_enseignants', function (Blueprint $t) {
            $t->id();
            $t->integer('enseignant_id');
            $t->integer('institut_financier_id');
            $t->boolean('est_actif');
            $t->boolean('est_principal');
        });
        Schema::create('instituts_financieres', function (Blueprint $t) {
            $t->id();
            $t->string('libelle');
        });
        DB::table('instituts_financieres')->insert([['id' => 1, 'libelle' => 'Banque locale'], ['id' => 2, 'libelle' => 'Banque étrangère']]);
        foreach ([1, 2] as $id) {
            DB::table('payroll_payslip_lines')->insert(['payroll_payslip_id' => $id, 'code' => 'IPRES_SALARIE', 'label' => 'IPRES', 'category' => 'contribution', 'amount' => $id * 10]);
            DB::table('comptes_bancaires_enseignants')->insert(['enseignant_id' => $id, 'institut_financier_id' => $id, 'est_actif' => true, 'est_principal' => true]);
        }
        DB::table('comptes_bancaires_enseignants')->insert(['enseignant_id' => 1, 'institut_financier_id' => 2, 'est_actif' => true, 'est_principal' => false]);
        DB::table('payroll_payslips')->insert(['id' => 3, 'enseignant_id' => 3, 'payroll_period_id' => 1, 'gross_amount' => 50, 'net_amount' => 45, 'payment_status' => 'pending']);
        $this->getJson('/api/ia/dashboard')->assertOk()->assertJsonPath('data.indicateurs.bulletins_generes', 2)
            ->assertJsonPath('data.indicateurs.bulletins_payes', 1)->assertJsonPath('data.indicateurs.sommes_percues', 90);
        $this->getJson('/api/ia/paie?view=paid')->assertOk()->assertJsonPath('indicateurs.bulletins_generes', 2)
            ->assertJsonPath('indicateurs.bulletins_payes', 1)->assertJsonPath('bulletins.total', 1);
        $this->getJson('/api/ia/paie?view=contributions')->assertOk()->assertJsonCount(1, 'rapport.rows')
            ->assertJsonPath('rapport.rows.0.4', 10);
        $this->getJson('/api/ia/paie?view=workforce')->assertOk()->assertJsonCount(1, 'rapport.rows')
            ->assertJsonPath('rapport.rows.0.1', 2)->assertDontSee('Thiès');
        $banks = $this->getJson('/api/ia/paie?view=banks')->assertOk()->assertDontSee('Banque étrangère')->json('rapport.rows');
        $this->assertCount(2, $banks);
        $this->assertEquals(150, array_sum(array_column($banks, 2)));
        DB::table('role_permission')->whereIn('permission_id', DB::table('permissions')->whereIn('slug', ['paie.cotisations.read', 'paie.effectifs_ief.read', 'paie.recap_banque.read'])->select('id'))->delete();
        foreach (['contributions', 'workforce', 'banks'] as $view) {
            $this->getJson('/api/ia/paie?view='.$view)->assertForbidden();
        }
        $this->getJson('/api/ia/paie?view=unknown')->assertUnprocessable();
    }
}
