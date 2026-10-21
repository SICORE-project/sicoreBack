<?php
namespace Tests\Feature;

use App\Http\Controllers\Api\Parametrage\SpecialiteEnseignantController;
use App\Models\Parametrage\SpecialiteEnseignant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SpecialiteWithoutCodeTest extends TestCase
{
    public function test_creation_without_code_and_update_preserve_generated_code(): void
    {
        Schema::create('disciplines', function ($table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('libelle');
            $table->text('description')->nullable();
            $table->string('statut');
            $table->timestamps();
        });
        $controller = new SpecialiteEnseignantController;
        $response = $controller->store(Request::create('/', 'POST', ['libelle' => 'Mathématiques']));
        $this->assertSame(201, $response->getStatusCode());
        $item = SpecialiteEnseignant::firstOrFail();
        $this->assertSame('actif', $item->statut);
        $item->update(['statut' => 'inactif']);
        $code = $item->code;
        $this->assertNotEmpty($code);
        $this->assertLessThanOrEqual(20, strlen($code));
        $controller->update(Request::create('/', 'PUT', ['libelle' => 'Informatique']), $item);
        $this->assertSame($code, $item->fresh()->code);
        $this->assertSame('Informatique', $item->fresh()->libelle);
        $this->assertSame('inactif', $item->fresh()->statut);
    }
}
