<?php

namespace Tests\Feature;

use App\Models\Admin\Permission;
use App\Models\Admin\Role;
use App\Models\Admin\User;
use App\Models\indemnites;
use App\Models\Parametrage\LieuService;
use App\Models\Personnel\Enseignant;
use Database\Seeders\AgentDecpcSeeder;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AgentDecpcTest extends TestCase
{
    private User $agent;

    protected function setUp(): void
    {
        parent::setUp();
        Schema::create('type_roles', function (Blueprint $t) {
            $t->id();
            $t->string('code');
            $t->string('libelle');
            $t->boolean('est_actif');
            $t->timestamps();
        });
        Schema::create('roles', function (Blueprint $t) {
            $t->id();
            $t->string('nom');
            $t->string('slug');
            $t->string('description')->nullable();
            $t->integer('type_role_id');
            $t->boolean('est_actif');
            $t->timestamps();
        });
        Schema::create('permissions', function (Blueprint $t) {
            $t->id();
            foreach (['nom', 'slug', 'groupe', 'module', 'action'] as $column) {
                $t->string($column);
            }
            $t->boolean('est_actif');
            $t->timestamps();
        });
        Schema::create('role_permission', function (Blueprint $t) {
            $t->integer('role_id');
            $t->integer('permission_id');
        });
        Schema::create('users', function (Blueprint $t) {
            $t->id();
            $t->string('nom');
            $t->string('prenom');
            $t->string('email');
            $t->string('password');
            foreach (['role_id', 'lieu_service_id', 'ia_id', 'ief_id'] as $column) {
                $t->integer($column)->nullable();
            }
            $t->timestamps();
            $t->softDeletes();
        });
        Schema::create('lieu_de_services', function (Blueprint $t) {
            $t->id();
            $t->string('type');
            $t->string('libelle');
            $t->string('perimetre');
            $t->integer('ia_id')->nullable();
            $t->integer('ief_id')->nullable();
            $t->boolean('est_actif');
            $t->timestamps();
            $t->softDeletes();
        });
        Schema::create('enseignants', function (Blueprint $t) {
            $t->id();
            foreach (['nom', 'prenom', 'matricule', 'statut'] as $column) {
                $t->string($column)->nullable();
            }
            foreach (['ia_id', 'ief_id', 'lieu_service_id', 'corps_id', 'diplome_id'] as $column) {
                $t->integer($column)->nullable();
            }
            $t->date('date_naissance')->nullable();
            $t->date('date_prise_service')->nullable();
            $t->boolean('est_actif')->default(true);
            $t->timestamps();
            $t->softDeletes();
        });
        $teacher = new Enseignant;
        foreach (['ia', 'ief', 'corps', 'categorie', 'discipline', 'diplome', 'comptesBancaires', 'syndicats', 'mutuelles'] as $name) {
            $relation = $teacher->$name();
            $table = $relation->getRelated()->getTable();
            if (! Schema::hasTable($table)) {
                Schema::create($table, function (Blueprint $t) {
                    $t->id();
                    $t->integer('enseignant_id')->nullable();
                    $t->string('code')->nullable();
                    $t->string('libelle')->nullable();
                    $t->softDeletes();
                });
            }
            if ($relation instanceof BelongsToMany) {
                Schema::create($relation->getTable(), function (Blueprint $t) use ($relation) {
                    $t->integer($relation->getForeignPivotKeyName());
                    $t->integer($relation->getRelatedPivotKeyName());
                    foreach ($relation->getPivotColumns() as $column) {
                        $t->string($column)->nullable();
                    }
                });
            }
        }
        (require database_path('migrations/2026_09_21_000001_create_personnel_audit_logs_table.php'))->up();
        $this->seed(AgentDecpcSeeder::class);
        $structure = LieuService::create(['type' => 'DECPC', 'libelle' => 'DRH régionale', 'perimetre' => 'regional', 'ia_id' => 1, 'est_actif' => true]);
        $this->agent = User::create(['nom' => 'Diop', 'prenom' => 'Awa', 'email' => 'awa@example.test', 'password' => 'secret123',
            'role_id' => Role::where('slug', 'agent_decpc')->value('id'), 'lieu_service_id' => $structure->id]);
        DB::table('corps_enseignant')->insert([['id' => 1, 'code' => 'FONCTIONNAIRE', 'libelle' => 'Fonctionnaire'], ['id' => 2, 'code' => 'PC', 'libelle' => 'Contractuel']]);
        foreach ([1 => 1, 2 => 2, 3 => 1] as $id => $ia) {
            Enseignant::create(['id' => $id, 'nom' => 'Fall', 'prenom' => 'Agent '.$id, 'matricule' => 'MAT'.$id,
                'ia_id' => $ia, 'ief_id' => $id, 'lieu_service_id' => $structure->id,
                'corps_id' => $id === 3 ? 2 : 1, 'date_naissance' => $id === 3 ? null : '1990-01-01',
                'statut' => $id === 3 ? 'retraite' : 'en_activite', 'est_actif' => $id !== 3]);
        }
        (require database_path('migrations/2026_08_09_090000_create_type_indemnites_table.php'))->up();
        (require database_path('migrations/2026_08_09_090100_create_indemnites_table.php'))->up();
        (require database_path('migrations/2026_09_21_000003_create_decpc_audit_logs_table.php'))->up();
        DB::table('type_indemnites')->insert(['id' => 1, 'libelle' => 'Test', 'mode_calcul' => 'forfaitaire']);
        foreach ([1, 2] as $ia) {
            $beneficiary = User::create(['nom' => 'Beneficiaire', 'prenom' => 'Agent',
                'email' => "beneficiaire$ia@example.test", 'password' => 'secret123', 'ia_id' => $ia]);
            indemnites::create(['id' => $ia, 'utilisateur_id' => $beneficiary->id,
                'type_indemnite_id' => 1, 'statut' => 'calcule', 'montant_total' => 100 * $ia]);
        }
        Sanctum::actingAs($this->agent, ['*']);
    }

    private function grant(string $slug): void
    {
        $this->agent->role->permissions()->syncWithoutDetaching([Permission::where('slug', $slug)->value('id')]);
    }

    public function test_dashboard_and_navigation_are_scoped(): void
    {
        $this->getJson('/api/decpc/dashboard')->assertOk()
            ->assertJsonPath('data.indicateurs.total_agents', 2)
            ->assertJsonPath('data.indicateurs.indemnites_en_attente', 1)
            ->assertJsonPath('data.indicateurs.montant_indemnites_en_cours', 100)
            ->assertJsonCount(1, 'data.derniers_dossiers')
            ->assertJsonCount(2, 'data.modules');
        $this->getJson('/api/me')->assertOk()->assertJsonPath('user.decpc.dashboard_path', '/decpc/dashboard');
        $this->assertDatabaseHas('decpc_audit_logs', ['route' => 'api/decpc/dashboard']);
    }

    public function test_lists_and_direct_urls_cannot_escape_scope(): void
    {
        $this->getJson('/api/admin/personnel/enseignants')->assertOk()->assertJsonPath('meta.total', 2);
        $this->getJson('/api/admin/personnel/enseignants/2')->assertNotFound();
        $this->getJson('/api/indemnites')->assertOk()->assertJsonPath('data.total', 1);
        $this->getJson('/api/indemnites?utilisateur_id=3')->assertOk()->assertJsonPath('data.total', 0);
        $this->getJson('/api/indemnites/2')->assertNotFound();
        $this->getJson('/api/indemnites/2/frais')->assertNotFound();
        $this->getJson('/api/indemnites/1')->assertOk();
    }

    public function test_modules_and_writes_require_permissions(): void
    {
        $this->agent->role->permissions()->sync(Permission::whereIn('slug', ['enseignants.read', 'indemnites.read'])->pluck('id')->all());
        foreach (['admin/users', 'parametrage/lieux-service', 'convocations', 'services-faits',
            'pieces-justificatives', 'etat-paie-indemnites', 'enseignants', 'recruitment/batches',
            'payroll/pages/paie-avance-tabaski'] as $path) {
            $this->getJson('/api/'.$path)->assertForbidden();
        }
        $this->postJson('/api/indemnites', [])->assertForbidden();
        $this->putJson('/api/indemnites/1', [])->assertForbidden();
        $this->deleteJson('/api/indemnites/1')->assertForbidden();
        $this->postJson('/api/indemnites/valider-calcul', ['id' => 1])->assertForbidden();
    }

    public function test_updates_are_audited_and_cannot_change_scope_or_validate(): void
    {
        $this->agent->role->permissions()->detach(Permission::where('slug', 'indemnites.validate')->value('id'));
        $this->grant('indemnites.update');
        $this->putJson('/api/indemnites/1', ['montant_total' => 125])->assertOk();
        $this->assertDatabaseHas('indemnites', ['id' => 1, 'montant_total' => 125]);
        $this->assertDatabaseHas('decpc_audit_logs', ['action' => 'PUT', 'route' => 'api/indemnites/{indemnite}']);
        $this->putJson('/api/indemnites/2', ['montant_total' => 1])->assertNotFound();
        $this->putJson('/api/indemnites/1', ['utilisateur_id' => 3])->assertForbidden();
        $this->putJson('/api/indemnites/1', ['statut' => 'valide'])->assertForbidden();
        $this->putJson('/api/indemnites/1', ['statut' => 'rejete'])->assertForbidden();
        $this->assertDatabaseCount('decpc_audit_logs', 1);
    }

    public function test_validation_is_scoped_and_traced(): void
    {
        $this->grant('indemnites.validate');
        $this->postJson('/api/indemnites/valider-calcul', ['id' => 2])->assertNotFound();
        $this->postJson('/api/indemnites/valider-calcul', ['id' => 1])->assertOk();
        $this->assertDatabaseHas('indemnites', ['id' => 1, 'statut' => 'valide', 'valide_par' => $this->agent->id]);
        $this->assertDatabaseHas('decpc_audit_logs', ['action' => 'POST']);
        $this->grant('indemnites.update');
        $this->postJson('/api/indemnites/1/frais', ['montant' => 50])->assertUnprocessable();
    }

    public function test_create_checks_beneficiary_and_preserves_complementary_permissions(): void
    {
        $this->grant('indemnites.create');
        $this->seed(AgentDecpcSeeder::class);
        $payload = ['utilisateur_id' => 2, 'type_indemnite_id' => 1, 'montant_total' => 50];
        $this->postJson('/api/indemnites', $payload)->assertCreated();
        $this->postJson('/api/indemnites', array_replace($payload, ['utilisateur_id' => 3]))->assertForbidden();
        $this->assertDatabaseCount('decpc_audit_logs', 1);
    }

    public function test_invalid_structure_is_closed_and_national_scope_respects_individual_restrictions(): void
    {
        $this->agent->lieuService->update(['perimetre' => 'national']);
        $this->getJson('/api/indemnites')->assertOk()->assertJsonPath('data.total', 2);
        $this->agent->ia_id = 2;
        $this->getJson('/api/indemnites')->assertOk()->assertJsonPath('data.total', 1)->assertJsonPath('data.data.0.id', 2);
        $this->agent->lieuService->update(['est_actif' => false]);
        $this->getJson('/api/indemnites')->assertOk()->assertJsonPath('data.total', 0);
        $this->agent->lieuService->update(['est_actif' => true, 'type' => 'DRH']);
        $this->getJson('/api/indemnites/1')->assertNotFound();
    }

    public function test_personnel_write_cannot_target_or_move_outside_scope(): void
    {
        $this->grant('enseignants.update');
        $this->putJson('/api/admin/personnel/enseignants/2', ['nom' => 'Changed'])->assertNotFound();
        $this->putJson('/api/admin/personnel/enseignants/1', ['ia_id' => 2])->assertForbidden();
    }

    public function test_login_returns_decpc_destination(): void
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
        $this->postJson('/api/login', ['email' => $this->agent->email, 'password' => 'secret123'])
            ->assertOk()->assertJsonPath('redirect_to', '/decpc/dashboard')
            ->assertJsonCount(2, 'decpc.modules');
    }

    public function test_audit_failure_rolls_back_the_write(): void
    {
        $this->grant('indemnites.update');
        Schema::drop('decpc_audit_logs');
        $this->putJson('/api/indemnites/1', ['montant_total' => 999])->assertStatus(500);
        $this->assertDatabaseHas('indemnites', ['id' => 1, 'montant_total' => 100]);
    }

    public function test_revoked_read_permissions_hide_modules_and_indicators(): void
    {
        $this->agent->role->permissions()->detach();
        $this->getJson('/api/indemnites')->assertForbidden();
        $this->getJson('/api/admin/personnel/enseignants')->assertForbidden();
        $this->getJson('/api/decpc/dashboard')->assertOk()->assertJsonCount(0, 'data.modules')
            ->assertJsonPath('data.indicateurs.total_agents', 0)
            ->assertJsonPath('data.indicateurs.indemnites_en_attente', 0)->assertJsonCount(0, 'data.derniers_dossiers');
    }

    public function test_calculation_uses_explicit_rate_and_scoped_beneficiary(): void
    {
        $this->grant('indemnites.create');
        DB::table('type_indemnites')->where('id', 1)->update(['montant_forfaitaire' => 250]);
        $payload = ['utilisateur_id' => 2, 'type_indemnite_id' => 1, 'frais_deplacement' => 50];
        $this->postJson('/api/indemnites/calculer', $payload)->assertCreated()->assertJsonPath('data.montant_total', '300.00');
        $this->postJson('/api/indemnites/simuler', array_replace($payload, ['utilisateur_id' => 3]))->assertForbidden();
        $this->postJson('/api/indemnites/calculer', array_replace($payload, ['convocation_id' => 1]))->assertForbidden();
        $this->getJson('/api/type-indemnites')->assertOk();
        $this->postJson('/api/type-indemnites', [])->assertForbidden();
    }

    public function test_agent_receives_all_module_permissions_and_decpc_permissions(): void
    {
        $decpc = Role::create(['nom' => 'DECPC', 'slug' => 'decpc',
            'type_role_id' => $this->agent->role->type_role_id, 'est_actif' => true]);
        $extra = Permission::create(['nom' => 'Exporter', 'slug' => 'enseignants.export',
            'groupe' => 'personnel', 'module' => 'enseignants', 'action' => 'export', 'est_actif' => true]);
        $decpc->permissions()->attach($extra->id);
        $this->seed(AgentDecpcSeeder::class);
        $this->seed(AgentDecpcSeeder::class);
        $this->assertEqualsCanonicalizing($decpc->permissions()->pluck('permissions.id')->all(),
            $this->agent->role->permissions()->pluck('permissions.id')->all());
        foreach (['enseignants', 'indemnites'] as $module) {
            foreach (['read', 'create', 'update', 'delete', 'manage', 'validate'] as $action) {
                $this->assertTrue($this->agent->hasPermission("$module.$action"));
            }
        }
        $this->putJson('/api/indemnites/1', ['montant_total' => 125])->assertOk();
        $this->postJson('/api/indemnites/valider-calcul', ['id' => 1])->assertOk();
    }
}
