<?php
namespace Tests\Feature;

use App\Http\Controllers\Api\Parametrage\CorpsController;
use App\Models\Parametrage\CorpsEnseignant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CorpsDeletionTest extends TestCase
{
    public function test_unused_corps_is_deleted_and_linked_corps_is_preserved(): void
    {
        Schema::create('corps_enseignant', function ($table) { $table->id(); $table->string('libelle'); $table->timestamps(); });
        Schema::create('categories', function ($table) { $table->id(); $table->unsignedBigInteger('corps_id'); });
        Schema::create('enseignants', function ($table) { $table->id(); $table->unsignedBigInteger('corps_id'); $table->softDeletes(); });
        Schema::create('rubrique_paies', function ($table) { $table->id(); });
        Schema::create('rubrique_par_corps', function ($table) { $table->unsignedBigInteger('corps_id'); $table->unsignedBigInteger('rubrique_paie_id'); });
        $controller = new CorpsController;
        $unused = CorpsEnseignant::create(['libelle' => 'Inutilisé']);
        $this->assertSame(200, $controller->destroy($unused->id)->getStatusCode());
        $this->assertDatabaseMissing('corps_enseignant', ['id' => $unused->id]);
        $linked = CorpsEnseignant::create(['libelle' => 'Utilisé']);
        DB::table('enseignants')->insert(['corps_id' => $linked->id]);
        $response = $controller->destroy($linked->id);
        $this->assertSame(409, $response->getStatusCode());
        $this->assertStringContainsString('des enseignants', $response->getData()->message);
        $this->assertDatabaseHas('corps_enseignant', ['id' => $linked->id]);
    }
}
