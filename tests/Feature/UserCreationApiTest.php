<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class UserCreationApiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        foreach (['users', 'lieu_de_services', 'iefs', 'ias', 'roles'] as $table) {
            Schema::dropIfExists($table);
        }

        Schema::create('roles', fn (Blueprint $table) => [$table->id(), $table->string('libelle'), $table->timestamps()]);
        Schema::create('ias', fn (Blueprint $table) => [$table->id(), $table->string('code'), $table->string('libelle'), $table->timestamps()]);
        Schema::create('iefs', function (Blueprint $table) {
            $table->id();
            $table->string('code');
            $table->string('libelle');
            $table->unsignedBigInteger('ia_id')->nullable();
            $table->timestamps();
        });
        Schema::create('lieu_de_services', function (Blueprint $table) {
            $table->id();
            $table->string('code');
            $table->string('libelle');
            $table->timestamps();
        });
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('prenom');
            $table->string('nom');
            $table->string('email')->unique();
            $table->string('login')->nullable()->unique();
            $table->string('telephone')->nullable();
            $table->string('password');
            $table->string('statut')->default('actif');
            $table->unsignedBigInteger('role_id')->nullable();
            $table->unsignedBigInteger('lieu_service_id')->nullable();
            $table->unsignedBigInteger('ia_id')->nullable();
            $table->unsignedBigInteger('ief_id')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function test_administrator_can_create_a_user(): void
    {
        $admin = $this->administrator();
        $this->referenceData();
        Sanctum::actingAs($admin);

        $response = $this->postJson('/api/users', $this->validPayload());

        $response->assertCreated()->assertJsonPath('data.login', 'f.kane')->assertJsonPath('data.role.libelle', 'Gestionnaire');
        $created = User::where('login', 'f.kane')->firstOrFail();
        $this->assertTrue(Hash::check('MotDePasse123', $created->password));
        $this->assertSame(1, $created->created_by);
    }

    public function test_user_creation_rejects_duplicate_email_and_login(): void
    {
        $admin = $this->administrator();
        $this->referenceData();
        User::create(['prenom' => 'Ancien', 'nom' => 'Compte', 'email' => 'fatou@example.test', 'login' => 'f.kane', 'password' => 'secret123', 'role_id' => 2]);
        Sanctum::actingAs($admin);

        $this->postJson('/api/users', $this->validPayload())
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email', 'login']);
    }

    public function test_non_administrator_cannot_create_a_user(): void
    {
        $user = $this->userWithRole('Gestionnaire');
        $this->referenceData();
        Sanctum::actingAs($user);

        $this->postJson('/api/users', $this->validPayload())->assertForbidden();
    }

    private function administrator(): User
    {
        return $this->userWithRole('Administrateur');
    }

    private function userWithRole(string $role): User
    {
        $roleId = $role === 'Administrateur' ? 1 : 3;
        \DB::table('roles')->insert(['id' => $roleId, 'libelle' => $role, 'created_at' => now(), 'updated_at' => now()]);

        return User::create(['id' => $roleId, 'prenom' => 'Admin', 'nom' => 'SICORE', 'email' => "$roleId@example.test", 'login' => "user$roleId", 'password' => 'secret123', 'role_id' => $roleId]);
    }

    private function referenceData(): void
    {
        \DB::table('roles')->insertOrIgnore(['id' => 2, 'libelle' => 'Gestionnaire', 'created_at' => now(), 'updated_at' => now()]);
        \DB::table('ias')->insert(['id' => 1, 'code' => 'DK', 'libelle' => 'Dakar', 'created_at' => now(), 'updated_at' => now()]);
        \DB::table('iefs')->insert(['id' => 1, 'code' => 'DK1', 'libelle' => 'Dakar 1', 'ia_id' => 1, 'created_at' => now(), 'updated_at' => now()]);
        \DB::table('lieu_de_services')->insert(['id' => 1, 'code' => 'DIR', 'libelle' => 'Direction', 'created_at' => now(), 'updated_at' => now()]);
    }

    private function validPayload(): array
    {
        return ['prenom' => 'Fatou', 'nom' => 'Kane', 'email' => 'fatou@example.test', 'login' => 'f.kane', 'telephone' => '770000000', 'password' => 'MotDePasse123', 'password_confirmation' => 'MotDePasse123', 'role_id' => 2, 'structure_id' => 1, 'ia_id' => 1, 'ief_id' => 1, 'statut' => 'actif'];
    }
}
