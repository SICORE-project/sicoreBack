<?php

namespace Tests\Unit;

use App\Http\Requests\StoreDiplomeRequest;
use App\Http\Requests\UpdateDiplomeRequest;
use App\Models\Parametrage\Diplome;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class DiplomeCategoryTest extends TestCase
{
    public function test_category_is_unique_per_diploma_and_current_record_is_allowed(): void
    {
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        Schema::create('categories', function (Blueprint $table): void { $table->id(); });
        Schema::create('diplomes', function (Blueprint $table): void {
            $table->id();
            $table->string('libelle');
            $table->integer('categorie_id');
        });
        DB::table('categories')->insert([['id' => 1], ['id' => 2]]);
        DB::table('diplomes')->insert([
            ['id' => 1, 'libelle' => 'Licence', 'categorie_id' => 1],
            ['id' => 2, 'libelle' => 'Licence', 'categorie_id' => 2],
        ]);
        foreach ([StoreDiplomeRequest::class, UpdateDiplomeRequest::class] as $class) {
            foreach ([['Licence', 2, false], ['  Licence  ', 2, false], ['licence', 2, false], ['Master', 2, true], ['Licence', 1, $class === UpdateDiplomeRequest::class]] as [$label, $category, $valid]) {
                $request = $class::create('/diplomes/1', 'PUT', ['libelle' => $label, 'categorie_id' => $category]);
                $route = new Route('PUT', 'diplomes/{diplome}', fn () => null);
                $route->bind($request);
                $route->setParameter('diplome', Diplome::find(1));
                $request->setRouteResolver(fn () => $route);
                (new \ReflectionMethod($class, 'prepareForValidation'))->invoke($request);
                $this->assertSame(mb_strtoupper(trim($label), 'UTF-8'), $request->input('libelle'));
                $validator = Validator::make($request->all(), ['categorie_id' => $request->rules()['categorie_id']], $request->messages());
                $this->assertSame($valid, $validator->passes());
            }
        }
    }
}
