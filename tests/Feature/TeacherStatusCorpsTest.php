<?php

namespace Tests\Feature;

use App\Http\Requests\Administration\Personnel\StoreEnseignantRequest;
use App\Http\Requests\Administration\Personnel\UpdateEnseignantRequest;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class TeacherStatusCorpsTest extends TestCase
{
    public function test_status_controls_corps_category_and_contract_for_creation_and_update(): void
    {
        foreach (['statuts_enseignant', 'corps_enseignant'] as $table) {
            Schema::create($table, function (Blueprint $table) {
                $table->id();
                $table->string('code');
                $table->string('libelle');
            });
        }
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->integer('corps_id');
        });
        DB::table('statuts_enseignant')->insert([
            ['id' => 1, 'code' => 'CONTRACTUEL', 'libelle' => 'Contractuel'],
            ['id' => 2, 'code' => 'VAC', 'libelle' => 'Vacataire'],
            ['id' => 3, 'code' => 'FONC', 'libelle' => 'Fonctionnaire'],
        ]);
        DB::table('corps_enseignant')->insert([
            ['id' => 10, 'code' => 'PC', 'libelle' => 'Professeur contractuel'],
            ['id' => 20, 'code' => 'VAC', 'libelle' => 'Vacataire'],
            ['id' => 30, 'code' => 'PE', 'libelle' => 'Professeur'],
        ]);
        DB::table('categories')->insert([['id' => 1, 'corps_id' => 10], ['id' => 2, 'corps_id' => 30]]);

        foreach ([StoreEnseignantRequest::class, UpdateEnseignantRequest::class] as $class) {
            foreach ([1 => 10, 2 => 20, 3 => 30] as $status => $body) {
                $request = $class::create('/', 'POST', [
                    'statut_enseignant_id' => $status, 'corps_id' => 30,
                    'categorie_id' => 1, 'date_fin_contrat' => '2027-12-31',
                ]);
                (new \ReflectionMethod($request, 'prepareTeacherCorps'))->invoke($request);
                $this->assertSame($body, $request->integer('corps_id'));
                $this->assertSame($status === 1 ? 1 : null, $request->input('categorie_id'));
                $this->assertSame($status === 1 ? '2027-12-31' : null, $request->input('date_fin_contrat'));

                $rules = array_intersect_key($request->rules(), array_flip(['corps_id', 'categorie_id', 'date_fin_contrat']));
                $this->assertTrue(Validator::make($request->all(), $rules)->passes());
                if ($status === 1) {
                    $request->merge(['categorie_id' => 2]);
                    $this->assertTrue(Validator::make($request->all(), $rules)->fails());
                    $request->merge(['categorie_id' => null, 'date_fin_contrat' => null]);
                    $errors = Validator::make($request->all(), $rules)->errors();
                    $this->assertTrue($errors->has('categorie_id'));
                    $this->assertTrue($errors->has('date_fin_contrat'));
                }
                if ($status === 3) {
                    $request->merge(['corps_id' => 10]);
                    $this->assertTrue(Validator::make($request->all(), $rules)->fails());
                    $request->merge(['corps_id' => 20]);
                    $this->assertTrue(Validator::make($request->all(), $rules)->fails());
                }
            }
        }
    }
}
