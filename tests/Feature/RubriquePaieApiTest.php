<?php

namespace Tests\Feature;

use App\Models\admin\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RubriquePaieApiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

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
        Schema::create('rubrique_paies', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('libelle', 100);
            $table->string('type', 20);
            $table->string('periodicite', 20)->default('mensuelle');
            $table->timestamps();
        });
        Schema::create('corps_enseignant', function (Blueprint $table): void {
            $table->id();
            $table->string('code');
            $table->string('libelle');
            $table->timestamps();
        });
        Schema::create('rubrique_par_corps', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('corps_id');
            $table->foreignId('rubrique_paie_id');
            $table->decimal('taux_personnalise', 8, 2)->nullable();
            $table->decimal('montant_personnalise', 15, 2)->nullable();
            $table->boolean('est_applicable')->default(true);
            $table->string('formule_personnalisee')->nullable();
            $table->boolean('est_actif')->default(true);
            $table->timestamps();
        });
    }

    public function test_admin_peut_effectuer_le_crud_complet(): void
    {
        Sanctum::actingAs($this->user('super_admin'));

        $payload = $this->payload();
        $create = $this->postJson('/api/rubriques-paie', $payload)
            ->assertCreated()
            ->assertJsonPath('data.code', 'PRIME_TEST');
        $id = $create->json('data.id');

        $this->getJson('/api/rubriques-paie?search=prime_test')
            ->assertOk()
            ->assertJsonPath('data.data.0.id', $id)
            ->assertJsonPath('statistics.gains', 1);

        $updated = [...$payload, 'libelle' => 'Prime test modifiée', 'type' => 'retenue'];
        $this->putJson("/api/rubriques-paie/{$id}", $updated)
            ->assertOk()
            ->assertJsonPath('data.libelle', 'Prime test modifiée')
            ->assertJsonPath('data.type', 'retenue');

        $this->getJson("/api/rubriques-paie/{$id}")
            ->assertOk()
            ->assertJsonPath('data.code', 'PRIME_TEST');

        $this->deleteJson("/api/rubriques-paie/{$id}")->assertOk();
        $this->getJson("/api/rubriques-paie/{$id}")->assertNotFound();
    }

    public function test_suppression_est_bloquee_si_la_rubrique_est_liee_a_un_corps(): void
    {
        Sanctum::actingAs($this->user('admin'));
        $id = $this->postJson('/api/rubriques-paie', $this->payload())->json('data.id');
        $corpsId = DB::table('corps_enseignant')->insertGetId([
            'code' => 'CE_TEST',
            'libelle' => 'Corps test',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('rubrique_par_corps')->insert([
            'corps_id' => $corpsId,
            'rubrique_paie_id' => $id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->deleteJson("/api/rubriques-paie/{$id}")
            ->assertStatus(409)
            ->assertJsonPath('message', 'Cette rubrique est utilisée par un ou plusieurs corps et ne peut pas être supprimée.');
    }

    public function test_un_role_non_autorise_recoit_une_erreur_403(): void
    {
        Sanctum::actingAs($this->user('gestionnaire'));

        $this->getJson('/api/rubriques-paie')->assertForbidden();
        $this->postJson('/api/rubriques-paie', $this->payload())->assertForbidden();
    }

    private function user(string $roleSlug): User
    {
        $roleId = DB::table('roles')->insertGetId([
            'nom' => ucfirst(str_replace('_', ' ', $roleSlug)),
            'slug' => $roleSlug,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return User::query()->create([
            'nom' => 'Ndiaye',
            'prenom' => 'Test',
            'email' => $roleSlug.'@example.test',
            'password' => 'secret',
            'role_id' => $roleId,
        ]);
    }

    private function payload(): array
    {
        return [
            'code' => 'PRIME_TEST',
            'libelle' => 'Prime test',
            'type' => 'gain',
            'periodicite' => 'mensuelle',
        ];
    }
}
