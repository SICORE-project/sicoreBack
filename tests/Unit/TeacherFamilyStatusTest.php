<?php
namespace Tests\Unit;

use App\Http\Requests\Administration\Personnel\StoreEnseignantRequest;
use App\Http\Requests\Administration\Personnel\UpdateEnseignantRequest;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class TeacherFamilyStatusTest extends TestCase
{
    public function test_children_are_preserved_for_every_marital_state(): void
    {
        Schema::create('statuts_enseignant', function ($table) { $table->id(); $table->string('code'); $table->string('libelle'); });
        foreach ([StoreEnseignantRequest::class, UpdateEnseignantRequest::class] as $class) {
            foreach (['celibataire', 'marie', 'veuf', 'divorce'] as $state) {
                $request = $class::create('/', 'POST', ['situation_matrimoniale' => $state, 'nombre_enfants' => 3, 'nombre_femmes' => 2]);
                (new \ReflectionMethod($request, 'prepareForValidation'))->invoke($request);
                $this->assertSame(3, $request->input('nombre_enfants'));
                $this->assertSame(3.5, $request->input('nombre_parts_fiscales'));
                $this->assertSame($state === 'marie' ? 2 : 0, $request->input('nombre_femmes'));
            }
        }
    }
}
