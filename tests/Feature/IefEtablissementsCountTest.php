<?php

namespace Tests\Feature;

use App\Http\Controllers\Api\Parametrage\IefController;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class IefEtablissementsCountTest extends TestCase
{
    public function test_iefs_list_returns_the_number_of_establishments(): void
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

        // 2 établissements rattachés à une IEF (le supprimé et celui sans IEF ne comptent pas).
        $this->assertSame(['etablissements' => 2], $response['stats']);
    }
}
