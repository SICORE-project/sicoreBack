<?php

namespace Tests\Feature;

use App\Http\Controllers\Api\Parametrage\LieuServiceController;
use App\Models\Parametrage\LieuService;
use App\Services\Parametrage\IaService;
use App\Services\Parametrage\IefService;
use App\Services\Parametrage\RegionalStructureService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class RegionalOrganisationOptionsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Schema::create('regions', function (Blueprint $table) {
            $table->id();
            $table->softDeletes();
        });
        foreach (['ias', 'iefs', 'lieu_de_services'] as $name) {
            Schema::create($name, function (Blueprint $table) use ($name) {
                $table->id();
                $table->string('code')->unique();
                $table->string('libelle');
                $table->boolean('est_actif')->default(true);
                $table->unsignedBigInteger('region_id')->nullable();
                $table->unsignedBigInteger('ia_id')->nullable();
                if ($name === 'lieu_de_services') {
                    $table->unsignedBigInteger('ief_id')->nullable();
                    $table->string('type');
                    $table->string('perimetre');
                }
                $table->timestamps();
                $table->softDeletes();
            });
        }
    }

    public function test_crud_entries_are_available_with_their_iefs_and_stable_structure_ids(): void
    {
        $ias = app(IaService::class);
        $iefs = app(IefService::class);
        $dakar = $ias->create(['code' => 'IA-DK', 'libelle' => 'Dakar']);
        $thies = $ias->create(['code' => 'IA-TH', 'libelle' => 'Thies']);
        $first = $iefs->create(['code' => 'IEF-1', 'libelle' => 'Premiere', 'ia_id' => $dakar->id]);
        $iefs->create(['code' => 'IEF-2', 'libelle' => 'Deuxieme', 'ia_id' => $dakar->id]);
        $iefs->create(['code' => 'IEF-3', 'libelle' => 'Troisieme', 'ia_id' => $thies->id]);
        $options = app(LieuServiceController::class)->iaOptions()->getData(true)['data'];
        $this->assertCount(2, $options);
        $this->assertCount(2, $options[0]['iefs']);
        $this->assertCount(1, $options[1]['iefs']);
        $this->assertNotNull($options[0]['lieu_service_id']);
        $this->assertNotNull($options[0]['iefs'][0]['lieu_service_id']);

        $structure = app(RegionalStructureService::class)->sync($first);
        $iefs->update($first->id, ['libelle' => 'Renommee', 'ia_id' => $dakar->id]);
        $this->assertSame('Renommee', $structure->fresh()->libelle);
        $this->assertSame(5, LieuService::count());
        $iefs->changeStatus($first->id, false);
        $this->assertFalse($structure->fresh()->est_actif);
        $options = app(LieuServiceController::class)->iaOptions()->getData(true)['data'];
        $this->assertCount(1, $options[0]['iefs']);
    }
}
