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
    public function test_sigle_is_saved_updated_and_searchable(): void
    {
        Schema::create('corps_enseignant', function ($table) {
            $table->id(); $table->string('code', 20)->unique(); $table->string('libelle', 100);
            $table->string('description')->nullable(); $table->timestamps();
        });
        $controller = new CorpsController;
        $response = $controller->store(Request::create('/', 'POST', ['code' => ' metp ', 'libelle' => 'Maître enseignement technique professionnel']));
        $this->assertSame(201, $response->getStatusCode());
        $data = $response->getData(true)['data'];
        $this->assertSame('METP', $data['code']);
        $response = $controller->update(Request::create('/', 'PUT', ['code' => 'MEFPT', 'libelle' => $data['libelle']]), $data['id']);
        $this->assertSame('MEFPT', $response->getData(true)['data']['code']);
        $response = $controller->index(Request::create('/', 'GET', ['search' => 'mefpt']));
        $this->assertSame(1, $response->getData(true)['data']['total']);
        try {
            $controller->store(Request::create('/', 'POST', ['code' => 'MEFPT', 'libelle' => 'Autre corps']));
            $this->fail('Un sigle déjà utilisé doit être refusé.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('code', $exception->errors());
        }
    }

    public function test_long_labels_generate_bounded_unique_codes_even_with_two_digit_suffixes(): void
    {
        Schema::create('corps_enseignant', function ($table) {
            $table->id(); $table->string('code', 20)->unique(); $table->string('libelle', 100); $table->timestamps();
        });
        $controller = new CorpsController;
        $codes = [];
        for ($number = 1; $number <= 12; $number++) {
            $label = "METP Maitre d'Enseignement technique professionnel {$number}";
            $response = $controller->store(Request::create('/', 'POST', ['libelle' => $label]));
            $this->assertSame(201, $response->getStatusCode());
            $data = $response->getData(true)['data'];
            $this->assertSame($label, $data['libelle']);
            $this->assertLessThanOrEqual(20, strlen($data['code']));
            $codes[] = $data['code'];
        }
        $this->assertCount(12, array_unique($codes));
        $this->assertStringEndsWith('-12', $codes[11]);
    }

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
