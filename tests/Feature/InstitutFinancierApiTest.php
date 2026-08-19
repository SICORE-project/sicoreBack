<?php

namespace Tests\Feature;

use App\Http\Middleware\PermissionMiddleware;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class InstitutFinancierApiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([
            Authenticate::class,
            PermissionMiddleware::class,
        ]);

        Schema::create('instituts_financieres', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('libelle');
            $table->string('sigle')->nullable();
            $table->string('type_institution')->nullable();
            $table->boolean('est_actif')->default(true);
            $table->string('telephone')->nullable();
            $table->string('email')->nullable();
            $table->string('adresse')->nullable();
            $table->timestamps();
        });

        DB::table('instituts_financieres')->insert([
            ['code' => 'B001', 'libelle' => 'Banque Atlantique', 'sigle' => 'BA', 'type_institution' => 'banque', 'est_actif' => true, 'telephone' => '330000001', 'email' => 'ba@example.sn', 'adresse' => 'Dakar', 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'M001', 'libelle' => 'Mutuelle du Sénégal', 'sigle' => 'MS', 'type_institution' => 'microfinance', 'est_actif' => false, 'telephone' => null, 'email' => null, 'adresse' => null, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function test_liste_les_champs_attendus_et_filtre_la_recherche(): void
    {
        $this->getJson('/api/parametrage/institutions-financieres?search=Atlantique')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.code', 'B001')
            ->assertJsonStructure(['data' => [[
                'id', 'code', 'libelle', 'sigle', 'type_institution',
                'telephone', 'email', 'adresse', 'est_actif',
            ]], 'links', 'meta']);
    }

    public function test_filtre_par_type_et_statut(): void
    {
        $this->getJson('/api/parametrage/institutions-financieres?type_institution=microfinance&est_actif=0')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.sigle', 'MS')
            ->assertJsonPath('data.0.est_actif', false);
    }

    public function test_refuse_une_pagination_hors_limite(): void
    {
        $this->getJson('/api/parametrage/institutions-financieres?per_page=101')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('per_page');
    }

    public function test_cree_une_institution_financiere(): void
    {
        $payload = [
            'code' => ' if007 ',
            'libelle' => 'Nouvelle Banque du Sénégal',
            'sigle' => 'NBS',
            'type_institution' => 'Banque',
            'telephone' => '+221 33 800 00 00',
            'email' => 'contact@nbs.sn',
            'adresse' => 'Dakar',
            'est_actif' => true,
        ];

        $this->postJson('/api/parametrage/institutions-financieres', $payload)
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.code', 'IF007')
            ->assertJsonPath('data.est_actif', true);

        $this->assertDatabaseHas('instituts_financieres', [
            'code' => 'IF007',
            'libelle' => 'Nouvelle Banque du Sénégal',
            'est_actif' => true,
        ]);
    }

    public function test_refuse_un_code_duplique_et_un_email_invalide(): void
    {
        $this->postJson('/api/parametrage/institutions-financieres', [
            'code' => 'b001',
            'libelle' => 'Doublon',
            'type_institution' => 'Banque',
            'email' => 'email-invalide',
            'est_actif' => true,
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['code', 'email']);
    }

    public function test_exige_les_donnees_obligatoires_a_la_creation(): void
    {
        $this->postJson('/api/parametrage/institutions-financieres', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['code', 'libelle', 'type_institution', 'est_actif']);
    }

    public function test_modifie_une_institution_sans_changer_son_statut(): void
    {
        $payload = [
            'code' => ' b001 ',
            'libelle' => 'Banque Atlantique Sénégal',
            'sigle' => 'BAS',
            'type_institution' => 'Banque',
            'telephone' => '+221 33 811 11 11',
            'email' => 'contact@bas.sn',
            'adresse' => 'Plateau, Dakar',
        ];

        $this->putJson('/api/parametrage/institutions-financieres/1', $payload)
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.code', 'B001')
            ->assertJsonPath('data.libelle', 'Banque Atlantique Sénégal')
            ->assertJsonPath('data.est_actif', true);

        $this->assertDatabaseHas('instituts_financieres', [
            'id' => 1,
            'sigle' => 'BAS',
            'est_actif' => true,
        ]);
    }

    public function test_refuse_un_code_deja_utilise_lors_de_la_modification(): void
    {
        $this->putJson('/api/parametrage/institutions-financieres/1', [
            'code' => 'M001',
            'libelle' => 'Banque Atlantique',
            'type_institution' => 'Banque',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('code');
    }

    public function test_refuse_de_modifier_le_statut_depuis_la_route_de_modification(): void
    {
        $this->putJson('/api/parametrage/institutions-financieres/1', [
            'code' => 'B001',
            'libelle' => 'Banque Atlantique',
            'type_institution' => 'Banque',
            'est_actif' => false,
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('est_actif');

        $this->assertDatabaseHas('instituts_financieres', ['id' => 1, 'est_actif' => true]);
    }

    public function test_retourne_404_si_institution_a_modifier_est_introuvable(): void
    {
        $this->putJson('/api/parametrage/institutions-financieres/999', [
            'code' => 'IF999',
            'libelle' => 'Institution inconnue',
            'type_institution' => 'Banque',
        ])->assertNotFound();
    }

    public function test_desactive_une_institution_sans_la_supprimer(): void
    {
        $this->patchJson('/api/parametrage/institutions-financieres/1/statut', [
            'est_actif' => false,
        ])
            ->assertOk()
            ->assertJsonPath('message', 'Institution financière désactivée avec succès.')
            ->assertJsonPath('data.est_actif', false);

        $this->assertDatabaseHas('instituts_financieres', [
            'id' => 1,
            'code' => 'B001',
            'est_actif' => false,
        ]);
    }

    public function test_active_une_institution(): void
    {
        $this->patchJson('/api/parametrage/institutions-financieres/2/statut', [
            'est_actif' => true,
        ])
            ->assertOk()
            ->assertJsonPath('message', 'Institution financière activée avec succès.')
            ->assertJsonPath('data.est_actif', true);

        $this->assertDatabaseHas('instituts_financieres', ['id' => 2, 'est_actif' => true]);
    }

    public function test_exige_un_statut_booleen(): void
    {
        $this->patchJson('/api/parametrage/institutions-financieres/1/statut', [
            'est_actif' => 'inactif',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('est_actif');
    }

    public function test_retourne_404_si_institution_du_statut_est_introuvable(): void
    {
        $this->patchJson('/api/parametrage/institutions-financieres/999/statut', [
            'est_actif' => false,
        ])->assertNotFound();
    }
}
