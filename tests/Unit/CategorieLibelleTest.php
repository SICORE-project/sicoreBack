<?php

namespace Tests\Unit;

use App\Http\Requests\Parametrage\StoreCategorieRequest;
use App\Http\Requests\Parametrage\UpdateCategorieRequest;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class CategorieLibelleTest extends TestCase
{
    public function test_category_labels_must_be_unique_except_for_the_current_category(): void
    {
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        Schema::create('categories', function (Blueprint $table): void {
            $table->id();
            $table->string('libelle');
        });
        DB::table('categories')->insert([
            ['id' => 1, 'libelle' => 'Catégorie 1'],
            ['id' => 2, 'libelle' => 'Catégorie 2'],
        ]);

        foreach ([StoreCategorieRequest::class, UpdateCategorieRequest::class] as $class) {
            foreach (['Catégorie 2' => false, '  Catégorie 2  ' => false, 'Catégorie 3' => true, 'Catégorie 1' => $class === UpdateCategorieRequest::class] as $label => $valid) {
                $request = $class::create('/categories/1', 'PUT', ['libelle' => $label]);
                $route = new Route('PUT', 'categories/{id}', fn () => null);
                $route->bind($request);
                $request->setRouteResolver(fn () => $route);
                (new \ReflectionMethod($class, 'prepareForValidation'))->invoke($request);
                $validator = Validator::make($request->all(), ['libelle' => $request->rules()['libelle']], $request->messages());
                $this->assertSame($valid, $validator->passes());
                if (!$valid) {
                    $this->assertSame('Une catégorie avec ce libellé existe déjà.', $validator->errors()->first('libelle'));
                }
            }
        }
    }
}
