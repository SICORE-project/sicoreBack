<?php

namespace Tests\Feature;

use App\Http\Controllers\Api\Parametrage\IefController;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class IefEtablissementsCountTest extends TestCase
{
    public function test_iefs_list_counts_attached_establishments(): void
    {
        foreach (['ias', 'iefs'] as $table) {
            Schema::create($table, function (Blueprint $t) use ($table): void {
                $t->id();
                $t->string('code')->nullable();
                $t->string('libelle');
                if ($table === 'iefs') {
                    $t->foreignId('ia_id')->nullable();
                    foreach (['adresse', 'telephone', 'email', 'responsable'] as $column) {
                        $t->string($column)->nullable();
                    }
                }
                $t->timestamps();
                $t->softDeletes();
            });
        }
        Schema::create('lieu_de_services', function (Blueprint $t): void {
            $t->id();
            $t->string('libelle');
            $t->foreignId('ia_id')->nullable();
            $t->foreignId('ief_id')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });
        DB::table('ias')->insert(['id' => 1, 'code' => 'IA1', 'libelle' => 'IA de Test']);
        DB::table('iefs')->insert([
            ['id' => 1, 'code' => 'IEF1', 'libelle' => 'IEF Un', 'ia_id' => 1],
            ['id' => 2, 'code' => 'IEF2', 'libelle' => 'IEF Deux', 'ia_id' => 1],
        ]);
        DB::table('lieu_de_services')->insert([
            ['libelle' => 'Lycée A', 'ia_id' => 1, 'ief_id' => 1, 'deleted_at' => null],
            ['libelle' => 'Lycée B', 'ia_id' => 1, 'ief_id' => 1, 'deleted_at' => null],
            ['libelle' => 'Lycée supprimé', 'ia_id' => 1, 'ief_id' => 1, 'deleted_at' => now()],
            ['libelle' => 'Service IA', 'ia_id' => 1, 'ief_id' => null, 'deleted_at' => null],
        ]);

        $response = app(IefController::class)->index()->response(request())->getData(true);
        $counts = collect($response['data'])->pluck('etablissements_count', 'code')->all();

        $this->assertSame(['IEF1' => 2, 'IEF2' => 0], collect($counts)->sortKeys()->all());
        $this->assertSame(['etablissements' => 2, 'iefs_sans_etablissement' => 1], $response['stats']);
    }
}
