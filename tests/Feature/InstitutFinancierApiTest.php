<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class InstitutFinancierApiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware();

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
}
