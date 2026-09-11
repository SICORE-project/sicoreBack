<?php

namespace Tests\Unit;

use App\Http\Requests\Administration\Personnel\StoreEnseignantRequest;
use App\Http\Requests\Administration\Personnel\UpdateEnseignantRequest;
use Tests\TestCase;

class TeacherFamilyStatusTest extends TestCase
{
    public function test_single_teachers_have_no_family_counts_and_married_fields_can_be_empty(): void
    {
        \Illuminate\Support\Facades\Schema::create('corps_enseignant', function ($table) {
            $table->id();
            $table->string('code');
            $table->string('libelle');
        });
        foreach ([StoreEnseignantRequest::class, UpdateEnseignantRequest::class] as $class) {
            foreach ([
                ['est_en_couple' => '0', 'nombre_enfants' => 3, 'nombre_femmes' => 2, 'conjoint_travaille' => '1'],
                ['est_en_couple' => '1'],
                ['est_en_couple' => '1', 'nombre_enfants' => null, 'nombre_femmes' => null, 'conjoint_travaille' => null],
            ] as $payload) {
                $request = $class::create('/', 'POST', $payload);
                $method = new \ReflectionMethod($request, 'prepareForValidation');
                $method->invoke($request);
                $this->assertSame(0, $request->input('nombre_enfants'));
                $this->assertSame(0, $request->input('nombre_femmes'));
                $this->assertFalse($request->input('conjoint_travaille'));
            }
        }
    }
}
