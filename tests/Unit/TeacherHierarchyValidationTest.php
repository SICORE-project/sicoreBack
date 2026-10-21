<?php

namespace Tests\Unit;

use App\Http\Requests\Administration\Personnel\StoreEnseignantRequest;
use App\Http\Requests\Administration\Personnel\UpdateEnseignantRequest;
use App\Http\Requests\Parametrage\Ief\UpdateIefRequest;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Routing\Route;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class TeacherHierarchyValidationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Schema::create('corps_enseignant', function (Blueprint $table) {
            $table->id();
            $table->string('code');
            $table->string('libelle');
        });
        foreach (['ias', 'iefs', 'lieu_de_services', 'enseignants'] as $name) {
            Schema::create($name, function (Blueprint $table) use ($name) {
                $table->id();
                $table->softDeletes();
                if ($name !== 'ias') { $table->unsignedBigInteger('ia_id')->nullable(); }
                if (in_array($name, ['lieu_de_services', 'enseignants'])) { $table->unsignedBigInteger('ief_id')->nullable(); }
                if ($name === 'enseignants') {
                    $table->unsignedBigInteger('lieu_service_id')->nullable();
                    foreach (['corps_id', 'diplome_id', 'categorie_id', 'date_recrutement', 'date_fin_contrat'] as $field) {
                        $table->string($field)->nullable();
                    }
                }
            });
        }
        DB::table('ias')->insert([['id' => 1], ['id' => 2]]);
        DB::table('iefs')->insert([['id' => 1, 'ia_id' => 1], ['id' => 2, 'ia_id' => 2], ['id' => 3, 'ia_id' => 1]]);
        DB::table('lieu_de_services')->insert([
            ['id' => 1, 'ia_id' => 1, 'ief_id' => 1],
            ['id' => 2, 'ia_id' => 2, 'ief_id' => 2],
            ['id' => 3, 'ia_id' => 1, 'ief_id' => 3],
        ]);
        DB::table('enseignants')->insert(['id' => 1, 'ia_id' => 1, 'ief_id' => 1, 'lieu_service_id' => 1]);
    }

    private function validateHierarchy(string $class, array $fields)
    {
        $request = $class::create('/', 'POST', $fields);
        $request->setContainer($this->app);
        if ($class === UpdateEnseignantRequest::class) {
            $route = new Route('PUT', '/{id}', fn () => null);
            $route->bind(FormRequest::create('/1', 'PUT'));
            $request->setRouteResolver(fn () => $route);
        }
        (new \ReflectionMethod($request, 'prepareForValidation'))->invoke($request);
        $validator = Validator::make($request->all(), Arr::only($request->rules(), ['ia_id', 'ief_id', 'lieu_service_id']), $request->messages());
        $validator->after($request->after());
        return $validator;
    }

    public function test_creation_and_update_accept_matching_hierarchy_and_optional_establishment(): void
    {
        foreach ([StoreEnseignantRequest::class, UpdateEnseignantRequest::class] as $class) {
            foreach ([1, null] as $lieu) {
                $validator = $this->validateHierarchy($class, ['ia_id' => 1, 'ief_id' => 1, 'lieu_service_id' => $lieu]);
                $this->assertTrue($validator->passes(), $validator->errors()->toJson());
            }
        }
    }

    public function test_creation_and_update_reject_mismatched_hierarchy(): void
    {
        foreach ([StoreEnseignantRequest::class, UpdateEnseignantRequest::class] as $class) {
            foreach ([
                [['ia_id' => 1, 'ief_id' => 2, 'lieu_service_id' => null], 'ief_id'],
                [['ia_id' => 1, 'ief_id' => 1, 'lieu_service_id' => 2], 'lieu_service_id'],
                [['ia_id' => 1, 'ief_id' => 1, 'lieu_service_id' => 3], 'lieu_service_id'],
                [['ia_id' => [], 'ief_id' => 1], 'ia_id'],
            ] as [$fields, $error]) {
                $validator = $this->validateHierarchy($class, $fields);
                $this->assertFalse($validator->passes());
                $this->assertTrue($validator->errors()->has($error));
            }
        }
    }

    public function test_partial_update_checks_saved_parents_and_establishment(): void
    {
        foreach ([['ia_id' => 2], ['ief_id' => 3], ['lieu_service_id' => 2]] as $fields) {
            $this->assertFalse($this->validateHierarchy(UpdateEnseignantRequest::class, $fields)->passes());
        }
        $validator = $this->validateHierarchy(UpdateEnseignantRequest::class, ['ia_id' => 2, 'ief_id' => 2, 'lieu_service_id' => 2]);
        $this->assertTrue($validator->passes(), $validator->errors()->toJson());
        $validator = $this->validateHierarchy(UpdateEnseignantRequest::class, ['ief_id' => 3, 'lieu_service_id' => null]);
        $this->assertTrue($validator->passes());
        $this->assertNull($validator->validated()['lieu_service_id']);
        $this->assertTrue($this->validateHierarchy(UpdateEnseignantRequest::class, [])->passes());
    }

    public function test_deleted_structures_cannot_be_assigned(): void
    {
        foreach (['ias' => 'ia_id', 'iefs' => 'ief_id', 'lieu_de_services' => 'lieu_service_id'] as $table => $field) {
            DB::table($table)->where('id', 1)->update(['deleted_at' => now()]);
            foreach ([StoreEnseignantRequest::class, UpdateEnseignantRequest::class] as $class) {
                $validator = $this->validateHierarchy($class, ['ia_id' => 1, 'ief_id' => 1, 'lieu_service_id' => 1]);
                $this->assertFalse($validator->passes());
                $this->assertTrue($validator->errors()->has($field));
            }
            DB::table($table)->update(['deleted_at' => null]);
        }
    }

    public function test_ief_cannot_change_ia_with_establishments_or_teachers_attached(): void
    {
        $request = UpdateIefRequest::create('/1', 'PUT', ['ia_id' => 2]);
        $route = new Route('PUT', '/{id}', fn () => null);
        $route->bind($request);
        $request->setRouteResolver(fn () => $route);
        foreach ([false, true] as $removeEstablishments) {
            if ($removeEstablishments) { DB::table('lieu_de_services')->where('ief_id', 1)->delete(); }
            $validator = Validator::make($request->all(), ['ia_id' => ['required', 'integer']]);
            $validator->after($request->after());
            $this->assertFalse($validator->passes());
            $this->assertTrue($validator->errors()->has('ia_id'));
        }
        DB::table('enseignants')->delete();
        $validator = Validator::make($request->all(), ['ia_id' => ['required', 'integer']]);
        $validator->after($request->after());
        $this->assertTrue($validator->passes());
    }
}
