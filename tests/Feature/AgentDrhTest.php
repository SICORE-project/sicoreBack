<?php

namespace Tests\Feature;

use App\Models\Admin\Permission;
use App\Models\Admin\Role;
use App\Models\Admin\User;
use App\Models\Parametrage\LieuService;
use App\Models\Personnel\Enseignant;
use Database\Seeders\AgentDrhSeeder;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AgentDrhTest extends TestCase
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
        $this->seed(AgentDrhSeeder::class);
        $structure = LieuService::create(['type' => 'DRH', 'libelle' => 'DRH régionale', 'perimetre' => 'regional', 'ia_id' => 1, 'est_actif' => true]);
        $this->agent = User::create(['nom' => 'Diop', 'prenom' => 'Awa', 'email' => 'awa@example.test', 'password' => 'secret123',
            'role_id' => Role::where('slug', 'agent_drh')->value('id'), 'lieu_service_id' => $structure->id]);
        DB::table('corps_enseignant')->insert([['id' => 1, 'code' => 'FONCTIONNAIRE', 'libelle' => 'Fonctionnaire'], ['id' => 2, 'code' => 'PC', 'libelle' => 'Contractuel']]);
        foreach ([1 => 1, 2 => 2, 3 => 1] as $id => $ia) {
            Enseignant::create(['id' => $id, 'nom' => 'Fall', 'prenom' => 'Agent '.$id, 'matricule' => 'MAT'.$id,
                'ia_id' => $ia, 'ief_id' => $id, 'lieu_service_id' => $structure->id,
                'corps_id' => $id === 3 ? 2 : 1, 'date_naissance' => $id === 3 ? null : '1990-01-01',
                'statut' => $id === 3 ? 'retraite' : 'en_activite', 'est_actif' => $id !== 3]);
        }
        Sanctum::actingAs($this->agent, ['*']);
    }

    public function test_dashboard_is_scoped_and_audited(): void
    {
        $this->getJson('/api/drh/dashboard')->assertOk()
            ->assertJsonPath('data.indicateurs.total_agents', 2)
            ->assertJsonPath('data.indicateurs.enseignants_fonctionnaires', 1)
            ->assertJsonPath('data.indicateurs.enseignants_non_fonctionnaires', 1)
            ->assertJsonPath('data.indicateurs.dossiers_actifs', 1)
            ->assertJsonPath('data.indicateurs.dossiers_incomplets', 1)
            ->assertJsonCount(2, 'data.derniers_dossiers')
            ->assertJsonCount(1, 'data.modules')
            ->assertJsonPath('data.modules.0.key', 'personnel');
        $this->assertDatabaseHas('personnel_audit_logs', ['user_id' => $this->agent->id, 'route' => 'api/drh/dashboard']);
    }

    public function test_dashboard_distinguishes_service_waiting_and_declared_abandon(): void
    {
        DB::table('enseignants')->where('id',1)->update(['date_prise_service'=>'2020-02-01']);
        DB::table('enseignants')->where('id',3)->update(['statut'=>'en_activite','est_actif'=>false]);
        DB::table('enseignants')->where('id',2)->update(['statut'=>'abandon']);
        $this->getJson('/api/drh/dashboard')->assertOk()
            ->assertJsonPath('data.indicateurs.prise_service_enregistree',1)
            ->assertJsonPath('data.indicateurs.attente_prise_service',1)
            ->assertJsonPath('data.indicateurs.enseignants_abandon',0);
        DB::table('enseignants')->where('id',1)->update(['statut'=>'abandon']);
        $this->getJson('/api/drh/dashboard')->assertOk()
            ->assertJsonPath('data.indicateurs.prise_service_enregistree',0)
            ->assertJsonPath('data.indicateurs.enseignants_abandon',1);
        DB::table('enseignants')->where('id',3)->update(['est_actif'=>true]);
        $this->getJson('/api/drh/dashboard')->assertOk()->assertJsonPath('data.indicateurs.attente_prise_service',0);
    }

    public function test_list_search_and_direct_access_cannot_escape_scope(): void
    {
        $this->getJson('/api/admin/personnel/enseignants')->assertOk()->assertJsonPath('meta.total', 2);
        $this->getJson('/api/admin/personnel/enseignants?ia_id=2&search=Fall')->assertOk()->assertJsonPath('meta.total', 0);
        $this->getJson('/api/admin/personnel/enseignants/2')->assertNotFound();
        $this->getJson('/api/admin/personnel/enseignants/1')->assertOk();
        $this->assertDatabaseHas('personnel_audit_logs', ['enseignant_id' => 1, 'action' => 'GET']);
    }

    public function test_unauthorized_writes_and_modules_are_denied(): void
    {
        $this->postJson('/api/admin/personnel/enseignants', [])->assertForbidden();
        $this->putJson('/api/admin/personnel/enseignants/1', [])->assertForbidden();
        $this->deleteJson('/api/admin/personnel/enseignants/1')->assertForbidden();
        foreach (['admin/users', 'parametrage/lieux-service', 'convocations', 'payroll/pages/paie-avance-tabaski'] as $path) {
            $this->getJson('/api/'.$path)->assertForbidden();
        }
        $this->assertDatabaseCount('personnel_audit_logs', 0);
    }

    public function test_extra_permission_does_not_allow_out_of_scope_update_or_transfer(): void
    {
        $this->agent->role->permissions()->attach(Permission::where('slug', 'enseignants.update')->value('id'));
        $this->putJson('/api/admin/personnel/enseignants/2', ['nom' => 'Changed'])->assertForbidden();
        $this->putJson('/api/admin/personnel/enseignants/1', ['ia_id' => 2])->assertForbidden();
        $this->assertDatabaseHas('enseignants', ['id' => 1, 'ia_id' => 1]);
    }

    public function test_no_structure_and_inactive_structure_grant_no_access(): void
    {
        $this->agent->lieuService->update(['est_actif' => false]);
        $this->getJson('/api/drh/dashboard')->assertOk()->assertJsonPath('data.indicateurs.total_agents', 0);
        $this->agent->setRelation('lieuService', null);
        $this->getJson('/api/admin/personnel/enseignants/1')->assertNotFound();
    }

    public function test_national_scope_and_individual_restriction(): void
    {
        $this->agent->lieuService->update(['perimetre' => 'national']);
        $this->getJson('/api/drh/dashboard')->assertOk()->assertJsonPath('data.indicateurs.total_agents', 3);
        $this->agent->ief_id = 3;
        $this->getJson('/api/drh/dashboard')->assertOk()->assertJsonPath('data.indicateurs.total_agents', 1);
    }

    public function test_me_returns_navigation_and_seeder_preserves_extra_permissions(): void
    {
        $this->agent->role->permissions()->attach(Permission::where('slug', 'enseignants.update')->value('id'));
        $this->seed(AgentDrhSeeder::class);
        $this->assertSame(5, $this->agent->role->permissions()->count());
        $this->assertFalse($this->agent->hasPermission('enseignants.update'));
        $this->assertTrue($this->agent->hasPermission('recruitment.import'));
        $this->getJson('/api/me')->assertOk()->assertJsonPath('user.drh.dashboard_path', '/drh/dashboard');
    }

    public function test_direct_delete_is_denied_even_with_legacy_permission(): void
    {
        $permission = Permission::create(['nom' => 'Supprimer', 'slug' => 'enseignants.delete', 'groupe' => 'personnel',
            'module' => 'enseignants', 'action' => 'delete', 'est_actif' => true]);
        $this->agent->role->permissions()->attach($permission->id);
        $this->deleteJson('/api/admin/personnel/enseignants/1')->assertForbidden();
        $this->assertDatabaseHas('enseignants', ['id' => 1, 'deleted_at' => null]);
        $this->assertDatabaseCount('personnel_audit_logs',0);
        $this->assertDatabaseHas('enseignants', ['id' => 3, 'deleted_at' => null]);
    }

    public function test_login_provides_drh_destination(): void
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
        $this->postJson('/api/login', ['email' => 'awa@example.test', 'password' => 'secret123'])
            ->assertOk()->assertJsonPath('redirect_to', '/drh/dashboard')
            ->assertJsonPath('drh.modules.0.key', 'personnel');
    }

    public function test_director_setup_preserves_credentials_and_is_repeatable(): void
    {
        Schema::table('lieu_de_services', function (Blueprint $t) {
            $t->string('code')->nullable()->unique();
        });
        Schema::table('users', function (Blueprint $t) {
            $t->string('fonction')->nullable();
        });
        $this->agent->update(['email' => 'mamedieye.dieng@sicore.sn']);
        $this->agent->role->update(['slug' => 'drh']);
        $password = $this->agent->password;
        $permissions = $this->agent->role->permissions()->pluck('permissions.id')->all();
        $this->seed(\Database\Seeders\DirecteurDrhStructureSeeder::class);
        $this->seed(\Database\Seeders\DirecteurDrhStructureSeeder::class);
        $this->agent->refresh();
        $this->assertSame($password, $this->agent->password);
        $this->assertSame($permissions, $this->agent->role->permissions()->pluck('permissions.id')->all());
        $this->assertSame('Directeur des ressources humaines', $this->agent->role->nom);
        $this->assertSame(1, LieuService::where('code', 'DRH')->count());
        $this->assertSame('national', app(\App\Services\Administration\Personnel\DrhScope::class)->describe($this->agent)['type']);
    }
}
