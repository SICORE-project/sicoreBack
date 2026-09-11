<?php

namespace Tests\Unit;

use App\Http\Requests\Administration\Personnel\StoreEnseignantRequest;
use App\Http\Requests\Administration\Personnel\UpdateEnseignantRequest;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Routing\Route;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Validator as RequestValidator;
use Tests\TestCase;

class TeacherContractValidationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');

        Schema::create('corps_enseignant', function (Blueprint $table): void {
            $table->id();
            $table->string('libelle');
            $table->string('code');
        });
        Schema::create('categories', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('corps_id');
        });
        Schema::create('diplomes', function (Blueprint $table): void {
            $table->id();
            $table->string('libelle');
            $table->unsignedBigInteger('categorie_id');
            $table->decimal('salaire_brut', 15, 2);
        });
        Schema::create('enseignants', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('corps_id');
            $table->unsignedBigInteger('categorie_id')->nullable();
            $table->unsignedBigInteger('diplome_id')->nullable();
            $table->date('date_recrutement')->nullable();
            $table->date('date_fin_contrat')->nullable();
            $table->softDeletes();
        });

        DB::table('corps_enseignant')->insert([
            ['id' => 1, 'libelle' => 'Contractuel', 'code' => 'PC'],
            ['id' => 2, 'libelle' => 'Vacataire', 'code' => 'vac'],
        ]);
        DB::table('categories')->insert([
            ['id' => 1, 'corps_id' => 1],
            ['id' => 2, 'corps_id' => 1],
            ['id' => 3, 'corps_id' => 1],
            ['id' => 4, 'corps_id' => 2],
        ]);
        DB::table('diplomes')->insert([
            ['id' => 1, 'libelle' => 'Licence', 'categorie_id' => 1, 'salaire_brut' => 200000],
            ['id' => 2, 'libelle' => 'Licence', 'categorie_id' => 2, 'salaire_brut' => 250000],
        ]);
        DB::table('enseignants')->insert([
            'id' => 1, 'corps_id' => 1, 'categorie_id' => 1, 'diplome_id' => 1,
            'date_recrutement' => '2025-01-01', 'date_fin_contrat' => '2027-12-31',
        ]);
    }

    public function test_contractual_teachers_require_category_and_contract_end_date_on_creation_and_update(): void
    {
        foreach ([StoreEnseignantRequest::class, UpdateEnseignantRequest::class] as $class) {
            foreach ([[], ['categorie_id' => null, 'date_fin_contrat' => null], ['categorie_id' => '', 'date_fin_contrat' => '']] as $fields) {
                $validator = $this->validateContract($class::create('/', 'POST', ['corps_id' => 1] + $fields));

                $this->assertFalse($validator->passes());
                $this->assertSame('La catégorie est obligatoire pour le corps Contractuel.', $validator->errors()->first('categorie_id'));
                $this->assertSame('La date de fin du contrat est obligatoire pour un enseignant contractuel.', $validator->errors()->first('date_fin_contrat'));
            }
        }
    }

    public function test_non_contractual_teachers_can_omit_category_and_contract_end_date(): void
    {
        foreach ([StoreEnseignantRequest::class, UpdateEnseignantRequest::class] as $class) {
            $validator = $this->validateContract($class::create('/', 'POST', ['corps_id' => 2]));

            $this->assertTrue($validator->passes(), $validator->errors()->toJson());
            $this->assertEquals(150000, $validator->validated()['salaire_brut']);
        }
    }

    public function test_category_must_belong_to_the_selected_corps_and_have_a_salary_for_the_diploma(): void
    {
        foreach ([StoreEnseignantRequest::class, UpdateEnseignantRequest::class] as $class) {
            foreach ([3 => 'diplome_id', 4 => 'categorie_id'] as $category => $error) {
                $request = $class::create('/', 'POST', [
                    'corps_id' => 1, 'categorie_id' => $category, 'diplome_id' => 1,
                    'date_recrutement' => '2025-01-01', 'date_fin_contrat' => '2027-12-31',
                    'salaire_brut' => 999999,
                ]);
                $validator = $this->validateContract($request);

                $this->assertFalse($validator->passes());
                $this->assertTrue($validator->errors()->has($error));
                $this->assertNull($request->input('salaire_brut'));
            }
        }
    }

    public function test_salary_and_diploma_are_resolved_from_the_selected_category_on_creation_and_update(): void
    {
        foreach ([StoreEnseignantRequest::class, UpdateEnseignantRequest::class] as $class) {
            $validator = $this->validateContract($class::create('/', 'POST', [
                'corps_id' => 1, 'categorie_id' => 2, 'diplome_id' => 1,
                'date_recrutement' => '2025-01-01', 'date_fin_contrat' => '2027-12-31',
                'salaire_brut' => 999999,
            ]));

            $this->assertTrue($validator->passes(), $validator->errors()->toJson());
            $this->assertSame(2, $validator->validated()['diplome_id']);
            $this->assertEquals(250000, $validator->validated()['salaire_brut']);
        }
    }

    public function test_contract_end_date_must_not_precede_recruitment_but_recruitment_remains_optional(): void
    {
        foreach ([StoreEnseignantRequest::class, UpdateEnseignantRequest::class] as $class) {
            foreach (['2028-01-01' => false, '2027-12-31' => true, '2025-01-01' => true, '' => true] as $recruitment => $valid) {
                $validator = $this->validateContract($class::create('/', 'POST', [
                    'corps_id' => 1, 'categorie_id' => 1,
                    'date_recrutement' => $recruitment ?: null, 'date_fin_contrat' => '2027-12-31',
                ]));

                $this->assertSame($valid, $validator->passes(), $class.' '.$recruitment.' '.$validator->errors()->toJson());
            }
        }
    }

    public function test_partial_update_uses_saved_contract_fields_and_recalculates_salary_after_a_category_change(): void
    {
        $validator = $this->validateContract($this->updateRequest(['categorie_id' => 2]));

        $this->assertTrue($validator->passes(), $validator->errors()->toJson());
        $this->assertSame(1, $validator->validated()['corps_id']);
        $this->assertSame(2, $validator->validated()['diplome_id']);
        $this->assertEquals(250000, $validator->validated()['salaire_brut']);
        $this->assertSame('2027-12-31', $validator->validated()['date_fin_contrat']);
    }

    public function test_partial_update_cannot_clear_required_contract_fields_without_sending_the_corps(): void
    {
        $validator = $this->validateContract($this->updateRequest(['categorie_id' => null, 'date_fin_contrat' => null]));

        $this->assertFalse($validator->passes());
        $this->assertTrue($validator->errors()->has('categorie_id'));
        $this->assertTrue($validator->errors()->has('date_fin_contrat'));
    }

    public function test_changing_corps_without_a_category_clears_the_previous_contractual_category(): void
    {
        $validator = $this->validateContract($this->updateRequest(['corps_id' => 2]));

        $this->assertTrue($validator->passes(), $validator->errors()->toJson());
        $this->assertNull($validator->validated()['categorie_id']);
        $this->assertEquals(150000, $validator->validated()['salaire_brut']);
    }

    private function updateRequest(array $fields): UpdateEnseignantRequest
    {
        $request = UpdateEnseignantRequest::create('/enseignants/1', 'PUT', $fields);
        $route = new Route('PUT', 'enseignants/{id}', fn () => null);
        $route->bind($request);
        $route->setParameter('id', 1);
        $request->setRouteResolver(fn () => $route);

        return $request;
    }

    private function validateContract(FormRequest $request): RequestValidator
    {
        (new \ReflectionMethod($request, 'prepareForValidation'))->invoke($request);

        return Validator::make($request->all(), Arr::only($request->rules(), [
            'corps_id', 'categorie_id', 'diplome_id', 'salaire_brut', 'date_recrutement', 'date_fin_contrat',
        ]), $request->messages());
    }
}
