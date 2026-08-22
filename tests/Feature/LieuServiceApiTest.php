<?php

namespace Tests\Feature;

use App\Http\Middleware\PermissionMiddleware;
use App\Models\Parametrage\LieuService;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class LieuServiceApiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([Authenticate::class, PermissionMiddleware::class]);

        Schema::create('ias', function (Blueprint $table) {
            $table->id();
            $table->string('code');
            $table->string('libelle');
            $table->softDeletes();
        });
        Schema::create('iefs', function (Blueprint $table) {
            $table->id();
            $table->string('code');
            $table->string('libelle');
            $table->unsignedBigInteger('ia_id');
            $table->softDeletes();
        });
        Schema::create('lieu_de_services', function (Blueprint $table) {
            $table->id();
            $table->string('code');
            $table->string('libelle');
            $table->unsignedBigInteger('ia_id')->nullable();
            $table->unsignedBigInteger('ief_id')->nullable();
            $table->string('region')->nullable();
            $table->string('departement')->nullable();
            $table->string('commune')->nullable();
            $table->string('adresse')->nullable();
            $table->string('telephone')->nullable();
            $table->string('email')->nullable();
            $table->string('responsable')->nullable();
            $table->string('type')->nullable();
            $table->boolean('est_actif')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('enseignants', function (Blueprint $table) {
            $table->id();
            $table->string('matricule');
            $table->string('nom');
            $table->string('prenom');
            $table->unsignedBigInteger('lieu_service_id')->nullable();
            $table->unsignedBigInteger('ia_id')->nullable();
            $table->unsignedBigInteger('ief_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('affectations_enseignants', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('enseignant_id');
            $table->unsignedBigInteger('ia_id')->nullable();
            $table->unsignedBigInteger('ief_id')->nullable();
            $table->unsignedBigInteger('lieu_service_id')->nullable();
            $table->unsignedBigInteger('centre_formation_id')->nullable();
            $table->date('date_debut')->nullable();
            $table->date('date_fin')->nullable();
            $table->string('type')->default('affectation');
            $table->string('motif')->nullable();
            $table->text('observations')->nullable();
            $table->boolean('est_active')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        DB::table('ias')->insert([
            ['id' => 1, 'code' => 'IA-DK', 'libelle' => 'IA Dakar'],
            ['id' => 2, 'code' => 'IA-TH', 'libelle' => 'IA Thiès'],
        ]);
        DB::table('iefs')->insert(['id' => 1, 'code' => 'IEF-DK', 'libelle' => 'IEF Dakar', 'ia_id' => 1]);
        DB::table('lieu_de_services')->insert([
            ['id' => 1, 'code' => 'LS01', 'libelle' => 'École Plateau', 'commune' => 'Dakar', 'type' => 'ecole', 'ia_id' => 1, 'ief_id' => 1, 'est_actif' => true, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'code' => 'LS02', 'libelle' => 'Centre Thiès', 'commune' => 'Thiès', 'type' => 'centre', 'ia_id' => 2, 'ief_id' => 1, 'est_actif' => false, 'created_at' => now(), 'updated_at' => now()],
        ]);
        DB::table('enseignants')->insert([
            'id' => 1,
            'matricule' => 'MAT001',
            'nom' => 'Diop',
            'prenom' => 'Awa',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_liste_les_lieux_avec_leurs_relations_et_la_coherence(): void
    {
        $this->getJson('/api/parametrage/lieux-service?search=Plateau')
            ->assertOk()->assertJsonPath('success', true)->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.ia.code', 'IA-DK')
            ->assertJsonPath('data.0.ief.code', 'IEF-DK')
            ->assertJsonPath('data.0.hierarchie_coherente', true);
    }

    public function test_filtre_par_ia_type_et_statut_et_signale_une_incoherence(): void
    {
        $this->getJson('/api/parametrage/lieux-service?ia_id=2&type=centre&est_actif=0')
            ->assertOk()->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.code', 'LS02')
            ->assertJsonPath('data.0.est_actif', false)
            ->assertJsonPath('data.0.hierarchie_coherente', false);
    }

    public function test_valide_les_filtres_et_la_pagination(): void
    {
        $this->getJson('/api/parametrage/lieux-service?ia_id=abc&per_page=101')
            ->assertUnprocessable()->assertJsonValidationErrors(['ia_id', 'per_page']);
    }

    public function test_modifie_un_lieu_et_son_rattachement_territorial(): void
    {
        DB::table('iefs')->insert([
            'id' => 2,
            'code' => 'IEF-TH',
            'libelle' => 'IEF Thiès',
            'ia_id' => 2,
        ]);

        $this->putJson('/api/parametrage/lieux-service/1', [
            'code' => ' ls-01-bis ',
            'libelle' => ' École Plateau rénovée ',
            'ia_id' => 2,
            'ief_id' => 2,
        ])->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.code', 'LS-01-BIS')
            ->assertJsonPath('data.libelle', 'École Plateau rénovée')
            ->assertJsonPath('data.ia_id', 2)
            ->assertJsonPath('data.ief_id', 2)
            ->assertJsonPath('data.hierarchie_coherente', true);

        $this->assertDatabaseHas('lieu_de_services', [
            'id' => 1,
            'code' => 'LS-01-BIS',
            'libelle' => 'École Plateau rénovée',
            'ia_id' => 2,
            'ief_id' => 2,
        ]);
    }

    public function test_conserve_le_code_actuel_mais_refuse_le_code_d_un_autre_lieu(): void
    {
        $this->putJson('/api/parametrage/lieux-service/1', [
            'code' => 'LS01',
            'libelle' => 'École Plateau',
            'ia_id' => 1,
            'ief_id' => 1,
        ])->assertOk();

        $this->putJson('/api/parametrage/lieux-service/1', [
            'code' => 'LS02',
            'libelle' => 'École Plateau',
            'ia_id' => 1,
            'ief_id' => 1,
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('code');
    }

    public function test_refuse_une_ief_qui_n_appartient_pas_a_l_ia(): void
    {
        $this->putJson('/api/parametrage/lieux-service/1', [
            'code' => 'LS01',
            'libelle' => 'École Plateau',
            'ia_id' => 2,
            'ief_id' => 1,
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('ief_id');
    }

    public function test_retourne_404_si_le_lieu_n_existe_pas(): void
    {
        $this->putJson('/api/parametrage/lieux-service/999', [
            'code' => 'LS99',
            'libelle' => 'Lieu absent',
            'ia_id' => 1,
            'ief_id' => 1,
        ])->assertNotFound();
    }

    public function test_un_nouveau_lieu_est_actif_par_defaut(): void
    {
        $lieu = LieuService::create([
            'code' => 'LS03',
            'libelle' => 'Nouveau lieu',
            'ia_id' => 1,
            'ief_id' => 1,
        ]);

        $this->assertTrue($lieu->refresh()->est_actif);
    }

    public function test_desactive_puis_active_un_lieu_de_service(): void
    {
        $this->patchJson('/api/parametrage/lieux-service/1/statut', [
            'est_actif' => false,
        ])->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.est_actif', false);

        $this->assertDatabaseHas('lieu_de_services', ['id' => 1, 'est_actif' => false]);

        $this->patchJson('/api/parametrage/lieux-service/1/statut', [
            'est_actif' => true,
        ])->assertOk()
            ->assertJsonPath('data.est_actif', true);

        $this->assertDatabaseHas('lieu_de_services', ['id' => 1, 'est_actif' => true]);
    }

    public function test_exige_un_statut_booleen(): void
    {
        $this->patchJson('/api/parametrage/lieux-service/1/statut', [
            'est_actif' => 'inactif',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('est_actif');

        $this->patchJson('/api/parametrage/lieux-service/1/statut', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('est_actif');
    }

    public function test_retourne_404_si_le_lieu_du_statut_n_existe_pas(): void
    {
        $this->patchJson('/api/parametrage/lieux-service/999/statut', [
            'est_actif' => false,
        ])->assertNotFound();
    }

    public function test_affecte_un_enseignant_et_trace_le_gestionnaire(): void
    {
        $this->connecteGestionnaire(77);

        $this->postJson('/api/parametrage/enseignants/1/affectations', [
            'lieu_service_id' => 1,
            'date_debut' => '2026-08-22',
            'motif' => 'Première affectation',
        ])->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.enseignant_id', 1)
            ->assertJsonPath('data.lieu_service_id', 1)
            ->assertJsonPath('data.est_active', true)
            ->assertJsonPath('data.created_by', 77);

        $this->assertDatabaseHas('enseignants', [
            'id' => 1,
            'lieu_service_id' => 1,
            'ia_id' => 1,
            'ief_id' => 1,
        ]);
    }

    public function test_reaffecte_et_conserve_l_historique(): void
    {
        $this->connecteGestionnaire(77);
        DB::table('lieu_de_services')->insert([
            'id' => 3,
            'code' => 'LS03',
            'libelle' => 'École Thiès',
            'ia_id' => 2,
            'ief_id' => null,
            'est_actif' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('affectations_enseignants')->insert([
            'id' => 1,
            'enseignant_id' => 1,
            'lieu_service_id' => 1,
            'ia_id' => 1,
            'ief_id' => 1,
            'date_debut' => '2026-01-01',
            'type' => 'affectation',
            'est_active' => true,
            'created_by' => 50,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->postJson('/api/parametrage/enseignants/1/affectations', [
            'lieu_service_id' => 3,
            'date_debut' => '2026-02-01',
        ])->assertCreated()
            ->assertJsonPath('data.type', 'reaffectation');

        $this->assertDatabaseHas('affectations_enseignants', [
            'id' => 1,
            'est_active' => false,
            'updated_by' => 77,
        ]);
        $this->assertSame(
            '2026-01-31',
            \App\Models\Personnel\AffectationEnseignant::findOrFail(1)->date_fin->toDateString()
        );
        $this->assertDatabaseHas('affectations_enseignants', [
            'enseignant_id' => 1,
            'lieu_service_id' => 3,
            'est_active' => true,
            'created_by' => 77,
        ]);
        $this->assertDatabaseCount('affectations_enseignants', 2);
    }

    public function test_refuse_un_lieu_inactif_et_un_enseignant_absent(): void
    {
        $this->connecteGestionnaire(77);

        $payload = ['lieu_service_id' => 2, 'date_debut' => '2026-08-22'];
        $this->postJson('/api/parametrage/enseignants/1/affectations', $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('lieu_service_id');

        $payload['lieu_service_id'] = 1;
        $this->postJson('/api/parametrage/enseignants/999/affectations', $payload)
            ->assertNotFound();
    }

    private function connecteGestionnaire(int $id): void
    {
        $user = new class extends Authenticatable {};
        $user->setAttribute('id', $id);
        $user->exists = true;
        $this->actingAs($user);
    }
}
