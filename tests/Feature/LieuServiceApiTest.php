<?php

namespace Tests\Feature;

use App\Http\Middleware\PermissionMiddleware;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Database\Schema\Blueprint;
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
            $table->id(); $table->string('code'); $table->string('libelle');
            $table->softDeletes();
        });
        Schema::create('iefs', function (Blueprint $table) {
            $table->id(); $table->string('code'); $table->string('libelle');
            $table->unsignedBigInteger('ia_id');
            $table->softDeletes();
        });
        Schema::create('lieu_de_services', function (Blueprint $table) {
            $table->id(); $table->string('code'); $table->string('libelle');
            $table->unsignedBigInteger('ia_id')->nullable();
            $table->unsignedBigInteger('ief_id')->nullable();
            $table->string('region')->nullable(); $table->string('departement')->nullable();
            $table->string('commune')->nullable(); $table->string('adresse')->nullable();
            $table->string('telephone')->nullable(); $table->string('email')->nullable();
            $table->string('responsable')->nullable(); $table->string('type')->nullable();
            $table->boolean('est_actif')->default(true); $table->timestamps(); $table->softDeletes();
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
}
