<?php

namespace Tests\Feature;

use App\Models\Admin\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\PersonalAccessToken;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Schema::dropIfExists('personal_access_tokens');
        Schema::dropIfExists('users');
        Schema::dropIfExists('roles');

        Schema::create('roles', fn(Blueprint $t) => [
            $t->id(),
            $t->string('nom'),
            $t->string('slug')->nullable(),
            $t->timestamps()
        ]);

        Schema::create('users', function(Blueprint $t) {
            $t->id();
            $t->string('nom');
            $t->string('prenom');
            $t->string('email')->unique();
            $t->string('password');
            $t->unsignedBigInteger('role_id')->nullable();
            $t->unsignedBigInteger('enseignant_id')->nullable();
            $t->date('date_naiss')->nullable();
            $t->rememberToken();
            $t->timestamps();
            $t->softDeletes();
        });

        Schema::create('personal_access_tokens', function(Blueprint $t) {
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
                'nom' => 'Administrateur',
                'slug' => 'admin',
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }

        return User::create([
            'nom' => 'Diaw',
            'prenom' => 'Baye',
            'email' => 'bdiaw@example.com',
            'password' => Hash::make('secret123'),
            'role_id' => $role
        ]);
    }

    public function test_login_valide_me_et_logout(): void
    {
        $this->user();

        $login = $this->postJson('/api/login', [
            'email' => 'bdiaw@example.com',
            'password' => 'secret123'
        ])->assertOk()->assertJsonPath('user.role.nom', 'Administrateur');

        // Récupération du token (gestion adaptative de la clé 'token' ou 'access_token')
        $token = $login->json('token') ?? $login->json('access_token');

        // Si le token est toujours introuvable, cette assertion affichera le contenu réel reçu
        if (is_null($token)) {
            $this->fail("Le token n'a pas pu être extrait de la réponse. Contenu reçu : " . json_encode($login->json()));
        }

        $this->withToken($token)->getJson('/api/me')->assertOk()->assertJsonPath('user.email', 'bdiaw@example.com');

        $this->withToken($token)->postJson('/api/logout')->assertOk();
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_login_invalide_reste_generique(): void
    {
        $this->user();

        $this->postJson('/api/login', [
            'email' => 'bdiaw@example.com',
            'password' => 'faux'
        ])
        ->assertStatus(422)
        ->assertJson([
            'message' => 'Identifiants incorrects',
            'errors' => [
                'login' => ['Identifiants incorrects']
            ]
        ]);
    }

    public function test_compte_sans_role_est_refuse(): void
    {
        $this->user(null);

        $this->postJson('/api/login', [
            'email' => 'bdiaw@example.com',
            'password' => 'secret123'
        ])->assertForbidden();
    }

    public function test_token_expire_est_refuse(): void
    {
        $u = $this->user();
        $plain = $u->createToken('test')->plainTextToken;
        $id = strtok($plain, '|');

        PersonalAccessToken::whereKey($id)->update(['expires_at' => now()->subMinute()]);

        $this->withToken($plain)->getJson('/api/me')->assertUnauthorized();
    }
}
