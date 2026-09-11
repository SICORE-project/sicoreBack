<?php
namespace Tests\Feature;

use App\Http\Controllers\Api\Parametrage\CorpsController;
use App\Models\Parametrage\CorpsEnseignant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class CorpsUniqueLibelleTest extends TestCase
{
    public function test_duplicate_labels_are_rejected_without_blocking_unchanged_label(): void
    {
        Schema::create('corps_enseignant', function ($table) {
            $table->id(); $table->string('code'); $table->string('libelle'); $table->timestamps();
        });
        $first = CorpsEnseignant::create(['code' => 'A', 'libelle' => 'Contractuel']);
        $second = CorpsEnseignant::create(['code' => 'B', 'libelle' => 'Vacataire']);
        $controller = new CorpsController;
        foreach ([null, $second->id] as $id) {
            try {
                $request = Request::create('/', 'POST', ['libelle' => ' Contractuel ']);
                $id === null ? $controller->store($request) : $controller->update($request, $id);
                $this->fail('Le doublon doit être refusé.');
            } catch (ValidationException $error) {
                $this->assertSame(['Un corps enseignant avec ce libellé existe déjà.'], $error->errors()['libelle']);
            }
        }
        $this->assertSame(200, $controller->update(Request::create('/', 'PUT', ['libelle' => 'Contractuel']), $first->id)->getStatusCode());
        $this->assertSame(2, CorpsEnseignant::count());
    }
}
