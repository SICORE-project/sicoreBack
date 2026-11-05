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
                $this->assertSame($state === 'celibataire' ? 2.5 : 3.5, $request->input('nombre_parts_fiscales'));
                $this->assertSame($state === 'marie' ? 2 : 0, $request->input('nombre_femmes'));
            }
        }
    }
    public function test_parts_include_children_ignore_spouse_work_and_are_capped(): void
    {
        Schema::create('statuts_enseignant', function ($table) { $table->id(); $table->string('code'); $table->string('libelle'); });
        foreach ([StoreEnseignantRequest::class, UpdateEnseignantRequest::class] as $class) {
            foreach ([['celibataire', 0, 1.0], ['celibataire', 2, 2.0], ['marie', 0, 2.0], ['marie', 2, 3.0], ['celibataire', 10, 5.0], ['marie', 10, 5.0]] as [$state, $children, $expected]) {
                foreach ([false, true] as $spouseWorks) {
                    $request = $class::create('/', 'POST', ['situation_matrimoniale' => $state, 'nombre_enfants' => $children, 'conjoint_travaille' => $spouseWorks]);
                    (new \ReflectionMethod($request, 'prepareForValidation'))->invoke($request);
                    $this->assertSame($expected, (float) $request->input('nombre_parts_fiscales'));
                }
            }
        }
    }

}
