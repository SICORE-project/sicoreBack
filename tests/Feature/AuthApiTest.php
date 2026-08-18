<?php
namespace Tests\Feature;

use App\Models\admin\User;
use App\Http\Requests\Administration\StoreUserRequest;
use App\Http\Requests\Administration\UpdateUserRequest;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Laravel\Sanctum\PersonalAccessToken;
use Tests\TestCase;

class AuthApiTest extends TestCase {

    protected function setUp():void {
        parent::setUp();
        Schema::dropIfExists('personal_access_tokens');
        Schema::dropIfExists('users');
        Schema::dropIfExists('lieu_de_services');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('role_permission');
        Schema::dropIfExists('permissions');

        Schema::create('roles', function (Blueprint $t) {
            $t->id();
            $t->string('libelle');
            $t->string('niveau')->default('consultation');
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
            $t->string('perimetre')->default('regional');
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

    private function user(?int $role=1):User {
        if($role) {
            \DB::table('roles')->insertOrIgnore([
                'id' => $role,
                'libelle' => 'Administrateur',
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }
        return User::create([
            'nom' => 'Diaw',
            'prenom' => 'Baye',
            'email' => 'bdiaw@example.com',
            'password' => bcrypt('secret123'),
            'role_id' => $role
        ]);
    }

    public function test_login_valide_me_et_logout():void {
        $this->user();
        $response = $this->postJson('/api/login', [
            'email' => 'bdiaw@example.com',
            'password' => 'secret123'
        ])->assertOk()->assertJsonPath('user.role.libelle', 'Administrateur');

        // Vérifie les 2 clés possibles pour le token
        $token = $response->json('token') ?? $response->json('access_token');

        $this->assertNotNull($token, 'Le token ne doit pas être null');

        $this->withToken($token)->getJson('/api/me')->assertOk()->assertJsonPath('user.email', 'bdiaw@example.com');
        $this->withToken($token)->postJson('/api/logout')->assertOk();
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_login_invalide_reste_generique():void {
        $this->user();
        $this->postJson('/api/login', [
            'email' => 'bdiaw@example.com',
            'password' => 'faux'
        ])->assertStatus(422)
          ->assertJsonValidationErrors(['login'])
          ->assertJsonPath('message', 'Identifiants incorrects');
    }

    public function test_compte_sans_role_est_refuse():void {
        $this->user(null);
        $this->postJson('/api/login', [
            'email' => 'bdiaw@example.com',
            'password' => 'secret123'
        ])->assertForbidden()
          ->assertJsonPath('message', 'Aucun rôle associé à cet utilisateur');  // ← MODIFIÉ
    }

    public function test_token_expire_est_refuse():void {
        $u = $this->user();
        $plain = $u->createToken('test')->plainTextToken;
        $id = strtok($plain, '|');
        PersonalAccessToken::whereKey($id)->update(['expires_at' => now()->subMinute()]);
        $this->withToken($plain)->getJson('/api/me')->assertUnauthorized();
    }

    public function test_token_expire_invalide_reste_generique():void {
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
        $this->assertArrayHasKey('structure_organisationnelle_id', $validator->errors()->toArray());
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
            'structure_organisationnelle_id' => $structureId,
        ];
        $request = StoreUserRequest::create('/api/admin/users', 'POST', $data);

        $this->assertTrue(Validator::make($data, $request->rules())->fails());
    }

    public function test_admin_metier_est_refuse_hors_dage(): void
    {
        \DB::table('roles')->insert([
            'id' => 20, 'libelle' => 'Administrateur metier', 'niveau' => 'admin_metier',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        foreach (['IA', 'IEF', 'DRH', 'DECPC'] as $type) {
            $structureId = \DB::table('lieu_de_services')->insertGetId([
                'type' => $type, 'libelle' => $type, 'est_actif' => true,
                'created_at' => now(), 'updated_at' => now(),
            ]);
            $data = [
                'nom' => 'Ndiaye', 'prenom' => 'Awa',
                'email' => strtolower($type).'@example.com', 'password' => 'password123',
                'role_id' => 20, 'statut' => 'actif',
                'structure_organisationnelle_id' => $structureId,
            ];
            $request = StoreUserRequest::create('/api/admin/users', 'POST', $data);

            $validator = Validator::make($data, $request->rules());

            $this->assertTrue($validator->fails(), "Le type {$type} devrait etre refuse.");
            $this->assertArrayHasKey('structure_organisationnelle_id', $validator->errors()->toArray());
        }
    }

    public function test_admin_metier_est_accepte_pour_la_dage(): void
    {
        \DB::table('roles')->insert([
            'id' => 20, 'libelle' => 'Administrateur metier', 'niveau' => 'admin_metier',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $structureId = \DB::table('lieu_de_services')->insertGetId([
            'type' => 'DAGE', 'libelle' => 'DAGE', 'est_actif' => true,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $data = [
            'nom' => 'Ndiaye', 'prenom' => 'Awa', 'email' => 'dage@example.com',
            'password' => 'password123', 'role_id' => 20, 'statut' => 'actif',
            'structure_organisationnelle_id' => $structureId,
        ];
        $request = StoreUserRequest::create('/api/admin/users', 'POST', $data);

        $this->assertFalse(Validator::make($data, $request->rules())->fails());
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
        $structureRequest = $this->updateRequest($user->id, ['structure_organisationnelle_id' => $iefId]);
        $this->assertArrayHasKey('structure_organisationnelle_id', Validator::make($structureRequest->all(), $structureRequest->rules())->errors()->toArray());
    }

    private function updateRequest(int $userId, array $data): UpdateUserRequest
    {
        $request = UpdateUserRequest::create("/api/admin/users/{$userId}", 'PUT', $data);
        $request->setRouteResolver(function () use ($userId) {
            $route = new Route('PUT', '/api/admin/users/{id}', []);
            $route->bind(\Illuminate\Http\Request::create("/api/admin/users/{$userId}", 'PUT'));

            return $route;
        });

        return $request;
    }
}
