<?php

namespace Tests\Unit;

use App\Http\Requests\Administration\Personnel\StoreEnseignantRequest;
use App\Http\Requests\Administration\Personnel\UpdateEnseignantRequest;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class EnseignantSalaryTest extends TestCase
{
    public function test_salary_uses_both_diploma_and_category_for_creation_and_update(): void
    {
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        Schema::create('corps_enseignant', function (Blueprint $table): void {
            $table->id();
            $table->string('libelle');
            $table->string('code');
        });
        Schema::create('diplomes', function (Blueprint $table): void {
            $table->id();
            $table->string('libelle');
            $table->unsignedBigInteger('categorie_id');
            $table->decimal('salaire_brut', 15, 2);
        });
        DB::table('corps_enseignant')->insert([
            ['id' => 1, 'libelle' => 'Contractuel', 'code' => 'contractuel'],
            ['id' => 2, 'libelle' => 'Vacataire', 'code' => 'vac'],
        ]);
        DB::table('diplomes')->insert([
            ['id' => 1, 'libelle' => 'Licence', 'categorie_id' => 1, 'salaire_brut' => 200000],
            ['id' => 2, 'libelle' => 'Licence', 'categorie_id' => 2, 'salaire_brut' => 250000],
            ['id' => 3, 'libelle' => 'Master', 'categorie_id' => 2, 'salaire_brut' => 300000],
        ]);

        foreach ([StoreEnseignantRequest::class, UpdateEnseignantRequest::class] as $class) {
            foreach ([[1, 1, 1, 200000], [1, 2, 2, 250000], [2, 1, 1, 200000], [3, 2, 3, 300000], [1, 3, 1, null], [1, null, 1, null]] as [$diploma, $category, $expectedDiploma, $salary]) {
                $request = $class::create('/', 'POST', ['nombre_femmes' => null, 'corps_id' => 1, 'diplome_id' => $diploma, 'categorie_id' => $category, 'salaire_brut' => 999999]);
                (new \ReflectionMethod($class, 'prepareForValidation'))->invoke($request);
                $this->assertSame(0, $request->input('nombre_femmes'));
                $this->assertSame($expectedDiploma, $request->integer('diplome_id'));
                $salary === null
                    ? $this->assertNull($request->input('salaire_brut'))
                    : $this->assertEquals($salary, $request->input('salaire_brut'));
            }
            $request = $class::create('/', 'POST', ['corps_id' => 2, 'diplome_id' => 1]);
            (new \ReflectionMethod($class, 'prepareForValidation'))->invoke($request);
            $this->assertEquals(150000, $request->input('salaire_brut'));
        }
    }
}
