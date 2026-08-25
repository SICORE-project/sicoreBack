<?php

namespace Tests\Feature;

use App\Http\Controllers\Api\Parametrage\LieuServiceController;
use App\Http\Controllers\Api\UserController;
use App\Http\Middleware\PermissionMiddleware;
use App\Http\Requests\Administration\StoreUserRequest;
use App\Http\Requests\Administration\UpdateUserRequest;
use App\Models\admin\User;
use App\Models\Parametrage\LieuService;
use App\Services\Administration\OrganizationalScope;
use App\Services\Administration\UserService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\PersonalAccessToken;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Schema::dropIfExists('personal_access_tokens');
        Schema::dropIfExists('users');
        Schema::dropIfExists('lieu_de_services');
        Schema::dropIfExists('ias');
        Schema::dropIfExists('iefs');
        Schema::dropIfExists('regions');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('type_roles');
        Schema::dropIfExists('role_permission');
        Schema::dropIfExists('permissions');

        Schema::create('regions', function (Blueprint $t) {
            $t->id();
            $t->string('code')->nullable();
            $t->string('nom');
            $t->timestamps();
        });

        Schema::create('ias', function (Blueprint $t) {
            $t->id();
            $t->string('code');
            $t->string('libelle');
            $t->unsignedBigInteger('region_id')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });

        Schema::create('iefs', function (Blueprint $t) {
            $t->id();
            $t->string('code');
            $t->string('libelle');
            $t->unsignedBigInteger('ia_id');
            $t->timestamps();
            $t->softDeletes();
        });

        Schema::create('type_roles', function (Blueprint $t) {
            $t->id();
            $t->string('code')->unique();
            $t->string('libelle');
            $t->boolean('est_actif')->default(true);
            $t->timestamps();
        });

        \DB::table('type_roles')->insert([
            ['id' => 1, 'code' => 'systeme', 'libelle' => 'Système', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'code' => 'admin_metier', 'libelle' => 'Administration métier', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'code' => 'gestionnaire', 'libelle' => 'Gestion', 'created_at' => now(), 'updated_at' => now()],
        ]);

        Schema::create('roles', function (Blueprint $t) {
            $t->id();
            $t->string('libelle');
            $t->string('niveau')->default('consultation');
            $t->unsignedBigInteger('type_role_id')->default(3);
            $t->timestamps();
        });

        Schema::create('permissions', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('guard_name')->default('web');
            $t->timestamps();
        });

        Schema::create('role_permission', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('role_id');
            $t->unsignedBigInteger('permission_id');
            $t->timestamps();
        });

        Schema::create('users', function (Blueprint $t) {
            $t->id();
            $t->string('nom');
            $t->string('prenom');
            $t->string('email')->unique();
            $t->string('password');
            $t->unsignedBigInteger('role_id')->nullable();
            $t->unsignedBigInteger('lieu_service_id')->nullable();
            $t->unsignedBigInteger('enseignant_id')->nullable();
            $t->date('date_naiss')->nullable();
            $t->rememberToken();
            $t->timestamps();
            $t->softDeletes();
        });

        Schema::create('lieu_de_services', function (Blueprint $t) {
            $t->id();
            $t->string('code')->nullable();
            $t->string('type');
            $t->string('perimetre')->nullable();
            $t->string('libelle');
            $t->unsignedBigInteger('ia_id')->nullable();
            $t->unsignedBigInteger('ief_id')->nullable();
            $t->boolean('est_actif')->default(true);
            $t->timestamps();
            $t->softDeletes();
        });

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
    }

    private function user(?int $role = 1): User
    {
        if ($role) {
            \DB::table('roles')->insertOrIgnore([
                'id' => $role,
                'libelle' => 'Administrateur',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return User::create([
            'nom' => 'Diaw',
            'prenom' => 'Baye',
            'email' => 'bdiaw@example.com',
            'password' => bcrypt('secret123'),
            'role_id' => $role,
        ]);
    }

    public function test_login_valide_me_et_logout(): void
    {
        $this->user();
        $response = $this->postJson('/api/login', [
            'email' => 'bdiaw@example.com',
            'password' => 'secret123',
        ])->assertOk()->assertJsonPath('user.role.libelle', 'Administrateur');

        // Vérifie les 2 clés possibles pour le token
        $token = $response->json('token') ?? $response->json('access_token');

        $this->assertNotNull($token, 'Le token ne doit pas être null');

        $this->withToken($token)->getJson('/api/me')->assertOk()->assertJsonPath('user.email', 'bdiaw@example.com');
        $this->withToken($token)->postJson('/api/logout')->assertOk();
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_login_invalide_reste_generique(): void
    {
        $this->user();
        $this->postJson('/api/login', [
            'email' => 'bdiaw@example.com',
            'password' => 'faux',
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['login'])
            ->assertJsonPath('message', 'Identifiants incorrects');
    }

    public function test_compte_sans_role_est_refuse(): void
    {
        $this->user(null);
        $this->postJson('/api/login', [
            'email' => 'bdiaw@example.com',
            'password' => 'secret123',
        ])->assertForbidden()
            ->assertJsonPath('message', 'Aucun rôle associé à cet utilisateur');  // ← MODIFIÉ
    }

    public function test_token_expire_est_refuse(): void
    {
        $u = $this->user();
        $plain = $u->createToken('test')->plainTextToken;
        $id = strtok($plain, '|');
        PersonalAccessToken::whereKey($id)->update(['expires_at' => now()->subMinute()]);
        $this->withToken($plain)->getJson('/api/me')->assertUnauthorized();
    }

    public function test_token_expire_invalide_reste_generique(): void
    {
        $u = $this->user();
        $plain = $u->createToken('test')->plainTextToken;
        $id = strtok($plain, '|');
        PersonalAccessToken::whereKey($id)->update(['expires_at' => now()->subMinute()]);
        $this->withToken($plain)->getJson('/api/me')
            ->assertUnauthorized()
            ->assertJson(['message' => 'Non authentifié.']);
    }

    public function test_structure_est_obligatoire_pour_un_compte_metier(): void
    {
        \DB::table('roles')->insert([
            'id' => 10,
            'libelle' => 'Gestionnaire',
            'niveau' => 'gestionnaire',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $data = [
            'nom' => 'Ndiaye', 'prenom' => 'Awa',
            'email' => 'awa@example.com', 'password' => 'password123',
            'role_id' => 10, 'statut' => 'actif',
        ];
        $request = StoreUserRequest::create('/api/admin/users', 'POST', $data);
        $validator = Validator::make($data, $request->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('lieu_service_id', $validator->errors()->toArray());
    }

    public function test_structure_inactive_est_refusee(): void
    {
        \DB::table('roles')->insert([
            'id' => 10, 'libelle' => 'Gestionnaire', 'niveau' => 'gestionnaire',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $structureId = \DB::table('lieu_de_services')->insertGetId([
            'type' => 'IEF', 'libelle' => 'IEF inactive', 'est_actif' => false,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $data = [
            'nom' => 'Ndiaye', 'prenom' => 'Awa',
            'email' => 'awa@example.com', 'password' => 'password123',
            'role_id' => 10, 'statut' => 'actif',
            'lieu_service_id' => $structureId,
        ];
        $request = StoreUserRequest::create('/api/admin/users', 'POST', $data);

        $this->assertTrue(Validator::make($data, $request->rules())->fails());
    }

    public function test_admin_metier_est_refuse_hors_structure_nationale(): void
    {
        \DB::table('roles')->insert([
            'id' => 20, 'libelle' => 'Administrateur metier', 'niveau' => 'admin_metier',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        foreach (['IA', 'IEF'] as $type) {
            $structureId = \DB::table('lieu_de_services')->insertGetId([
                'type' => $type, 'libelle' => $type, 'est_actif' => true,
                'created_at' => now(), 'updated_at' => now(),
            ]);
            $data = [
                'nom' => 'Ndiaye', 'prenom' => 'Awa',
                'email' => strtolower($type).'@example.com', 'password' => 'password123',
                'role_id' => 20, 'statut' => 'actif',
                'lieu_service_id' => $structureId,
            ];
            $request = StoreUserRequest::create('/api/admin/users', 'POST', $data);

            $validator = Validator::make($data, $request->rules());

            $this->assertTrue($validator->fails(), "Le type {$type} devrait etre refuse.");
            $this->assertArrayHasKey('lieu_service_id', $validator->errors()->toArray());
        }
    }

    public function test_admin_metier_est_accepte_pour_une_structure_nationale(): void
    {
        \DB::table('roles')->insert([
            'id' => 20, 'libelle' => 'Administrateur metier', 'niveau' => 'admin_metier',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        foreach (['DRH', 'DAGE', 'DECPC'] as $type) {
            $structureId = \DB::table('lieu_de_services')->insertGetId([
                'type' => $type, 'libelle' => $type, 'est_actif' => true,
                'created_at' => now(), 'updated_at' => now(),
            ]);
            $data = [
                'nom' => 'Ndiaye', 'prenom' => 'Awa', 'email' => strtolower($type).'@example.com',
                'password' => 'password123', 'role_id' => 20, 'statut' => 'actif',
                'lieu_service_id' => $structureId,
            ];
            $request = StoreUserRequest::create('/api/admin/users', 'POST', $data);

            $this->assertFalse(Validator::make($data, $request->rules())->fails(), "Le type {$type} devrait etre accepte.");
        }
    }

    public function test_modification_role_ou_structure_verifie_la_matrice(): void
    {
        \DB::table('roles')->insert([
            ['id' => 20, 'libelle' => 'Administrateur metier', 'niveau' => 'admin_metier', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 21, 'libelle' => 'Gestionnaire', 'niveau' => 'gestionnaire', 'created_at' => now(), 'updated_at' => now()],
        ]);
        $iefId = \DB::table('lieu_de_services')->insertGetId([
            'type' => 'IEF', 'libelle' => 'IEF', 'est_actif' => true,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $dageId = \DB::table('lieu_de_services')->insertGetId([
            'type' => 'DAGE', 'libelle' => 'DAGE', 'est_actif' => true,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $user = User::create([
            'nom' => 'Diaw', 'prenom' => 'Baye', 'email' => 'matrix@example.com',
            'password' => 'password123', 'role_id' => 21,
            'lieu_service_id' => $iefId,
        ]);

        $roleRequest = $this->updateRequest($user->id, ['role_id' => 20]);
        $this->assertArrayHasKey('role_id', Validator::make($roleRequest->all(), $roleRequest->rules())->errors()->toArray());

        $user->update(['role_id' => 20, 'lieu_service_id' => $dageId]);
        $structureRequest = $this->updateRequest($user->id, ['lieu_service_id' => $iefId]);
        $this->assertArrayHasKey('lieu_service_id', Validator::make($structureRequest->all(), $structureRequest->rules())->errors()->toArray());
    }

    public function test_rattachement_dedie_applique_les_regles_role_structure(): void
    {
        \DB::table('roles')->insert([
            'id' => 20, 'libelle' => 'Administrateur metier', 'niveau' => 'admin_metier',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $dageId = \DB::table('lieu_de_services')->insertGetId([
            'type' => 'DAGE', 'libelle' => 'DAGE', 'est_actif' => true,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $iefId = \DB::table('lieu_de_services')->insertGetId([
            'type' => 'IEF', 'libelle' => 'IEF', 'est_actif' => true,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $user = User::create([
            'nom' => 'Fall', 'prenom' => 'Aminata', 'email' => 'attach@example.com',
            'password' => 'password123', 'role_id' => 20,
        ]);
        $controller = new UserController(app(UserService::class));

        $response = $controller->assignStructure(Request::create('/', 'POST', [
            'lieu_service_id' => $dageId,
        ]), (string) $user->id);

        $this->assertSame(200, $response->status());
        $this->assertSame($dageId, $user->fresh()->lieu_service_id);

        try {
            $controller->assignStructure(Request::create('/', 'POST', [
                'lieu_service_id' => $iefId,
            ]), (string) $user->id);
            $this->fail('Le rattachement d’un admin métier à une IEF devait être refusé.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('lieu_service_id', $exception->errors());
            $this->assertSame($dageId, $user->fresh()->lieu_service_id);
        }
    }

    public function test_perimetres_national_ia_et_ief_filtrent_les_donnees(): void
    {
        \DB::table('roles')->insert([
            'id' => 30, 'libelle' => 'Gestionnaire', 'niveau' => 'gestion',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $ia = LieuService::create(['code' => 'IA-1', 'type' => 'IA', 'perimetre' => 'regional', 'libelle' => 'IA 1', 'ia_id' => 10, 'est_actif' => true]);
        $ief = LieuService::create(['code' => 'IEF-1', 'type' => 'IEF', 'perimetre' => 'regional', 'libelle' => 'IEF 1', 'ia_id' => 10, 'ief_id' => 100, 'est_actif' => true]);
        LieuService::create(['code' => 'IEF-2', 'type' => 'IEF', 'perimetre' => 'regional', 'libelle' => 'IEF 2', 'ia_id' => 20, 'ief_id' => 200, 'est_actif' => true]);
        $national = LieuService::create(['code' => 'DAGE', 'type' => 'DAGE', 'perimetre' => 'national', 'libelle' => 'DAGE', 'est_actif' => true]);

        $scope = app(OrganizationalScope::class);
        $user = $this->user(30);

        $user->update(['lieu_service_id' => $ia->id]);
        $this->assertSame(2, $scope->apply(LieuService::query(), $user->fresh())->count());

        $user->update(['lieu_service_id' => $ief->id]);
        $this->assertSame(1, $scope->apply(LieuService::query(), $user->fresh())->count());

        $user->update(['lieu_service_id' => $national->id]);
        $this->assertSame(4, $scope->apply(LieuService::query(), $user->fresh())->count());

        $user->update(['lieu_service_id' => null]);
        $this->assertSame(0, $scope->apply(LieuService::query(), $user->fresh())->count());
    }

    public function test_structure_sans_perimetre_herite_du_type_national_ou_regional(): void
    {
        \DB::table('roles')->insert([
            'id' => 40, 'libelle' => 'Super Administrateur', 'niveau' => 'systeme',
            'type_role_id' => 1,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $user = User::create([
            'nom' => 'Sarr', 'prenom' => 'Amina', 'email' => 'legacy-structure@example.com',
            'password' => bcrypt('secret123'), 'role_id' => 40,
        ]);

        \DB::table('regions')->insert(['id' => 1, 'code' => 'DCK', 'nom' => 'Dakar', 'created_at' => now(), 'updated_at' => now()]);
        \DB::table('ias')->insert(['id' => 10, 'code' => 'IA-10', 'libelle' => 'IA Dakar', 'region_id' => 1, 'created_at' => now(), 'updated_at' => now()]);

        LieuService::create(['code' => 'DRH', 'type' => 'DRH', 'libelle' => 'DRH', 'est_actif' => true]);
        LieuService::create(['code' => 'IA-legacy', 'type' => 'IA', 'libelle' => 'IA Legacy', 'ia_id' => 10, 'est_actif' => true]);
        LieuService::create(['code' => 'IEF-legacy', 'type' => 'IEF', 'libelle' => 'IEF Legacy', 'ia_id' => 10, 'ief_id' => 20, 'est_actif' => true]);

        $request = Request::create('/api/lieux-service', 'GET');
        $request->setUserResolver(fn () => $user);

        $controller = new LieuServiceController;
        $response = $controller->index($request, app(OrganizationalScope::class));
        $payload = $response->getData(true);

        $this->assertNotEmpty($payload['perimetres']['national']);
        $this->assertNotEmpty($payload['perimetres']['regional']);
        $this->assertSame('national', $payload['perimetres']['national'][0]['perimetre']);
        $this->assertSame('regional', $payload['perimetres']['regional'][0]['ias'][0]['iefs'][0]['perimetre']);
    }

    public function test_liste_utilisateurs_retourne_un_tableau_directement_exploitable(): void
    {
        $this->user();
        $controller = new UserController(app(UserService::class));

        $response = $controller->all(Request::create('/api/admin/users/all', 'GET'))->response();
        $payload = $response->getData(true);

        $this->assertSame(200, $response->status());
        $this->assertTrue($payload['success']);
        $this->assertCount(1, $payload['data']);
        $this->assertSame('bdiaw@example.com', $payload['data'][0]['email']);
    }

    public function test_liste_utilisateurs_se_filtre_par_type_de_structure(): void
    {
        $iaId = \DB::table('lieu_de_services')->insertGetId([
            'type' => 'IA', 'libelle' => 'IA Dakar', 'est_actif' => true,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $iefId = \DB::table('lieu_de_services')->insertGetId([
            'type' => 'IEF', 'libelle' => 'IEF Dakar', 'est_actif' => true,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $this->user()->update(['lieu_service_id' => $iaId]);
        User::create([
            'nom' => 'Fall', 'prenom' => 'Awa', 'email' => 'awa@example.com',
            'password' => 'password123', 'lieu_service_id' => $iefId,
        ]);

        $request = Request::create('/api/admin/users', 'GET', ['type_structure' => 'ief']);
        $response = (new UserController(app(UserService::class)))->index($request)->response();
        $payload = $response->getData(true);

        $this->assertCount(1, $payload['data']);
        $this->assertSame('awa@example.com', $payload['data'][0]['email']);
        $this->assertSame('IEF', $payload['data'][0]['lieu_service']['type']);
    }

    public function test_un_lieu_de_service_est_cree_avec_une_ia_et_une_ief_coherentes(): void
    {
        \DB::table('ias')->insert([
            'id' => 10, 'code' => 'IA-10', 'libelle' => 'IA Dakar',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        \DB::table('iefs')->insert([
            'id' => 20, 'code' => 'IEF-20', 'libelle' => 'IEF Dakar', 'ia_id' => 10,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $response = $this->actingAs($this->user())
            ->withoutMiddleware(PermissionMiddleware::class)
            ->postJson('/api/lieux-service', [
                'code' => ' ls-001 ',
                'libelle' => ' École élémentaire ',
                'ia_id' => 10,
                'ief_id' => 20,
            ]);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.code', 'LS-001')
            ->assertJsonPath('data.ia_id', 10)
            ->assertJsonPath('data.ief_id', 20);

        $this->assertDatabaseHas('lieu_de_services', [
            'code' => 'LS-001',
            'libelle' => 'École élémentaire',
            'ia_id' => 10,
            'ief_id' => 20,
            'type' => 'IEF',
            'perimetre' => 'regional',
            'est_actif' => true,
        ]);
    }

    public function test_une_ief_rattachee_a_une_autre_ia_est_refusee(): void
    {
        foreach ([10, 11] as $iaId) {
            \DB::table('ias')->insert([
                'id' => $iaId, 'code' => "IA-{$iaId}", 'libelle' => "IA {$iaId}",
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }
        \DB::table('iefs')->insert([
            'id' => 20, 'code' => 'IEF-20', 'libelle' => 'IEF Dakar', 'ia_id' => 11,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->actingAs($this->user())
            ->withoutMiddleware(PermissionMiddleware::class)
            ->postJson('/api/lieux-service', [
                'code' => 'LS-002',
                'libelle' => 'École test',
                'ia_id' => 10,
                'ief_id' => 20,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('ief_id');

        $this->assertDatabaseMissing('lieu_de_services', ['code' => 'LS-002']);
    }

    private function updateRequest(int $userId, array $data): UpdateUserRequest
    {
        $request = UpdateUserRequest::create("/api/admin/users/{$userId}", 'PUT', $data);
        $request->setRouteResolver(function () use ($userId) {
            $route = new Route('PUT', '/api/admin/users/{id}', []);
            $route->bind(Request::create("/api/admin/users/{$userId}", 'PUT'));

            return $route;
        });

        return $request;
    }
}
