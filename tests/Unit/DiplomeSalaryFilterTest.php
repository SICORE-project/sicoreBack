<?php

namespace Tests\Unit;

use App\Http\Controllers\Api\DiplomeController;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class DiplomeSalaryFilterTest extends TestCase
{
    public function test_salary_filter_includes_boundaries_and_excludes_values_outside_range(): void
    {
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        Schema::create('diplomes', function (Blueprint $table): void {
            $table->id();
            $table->string('libelle');
            $table->integer('categorie_id')->nullable();
            $table->decimal('salaire_brut', 15, 2);
            $table->timestamps();
        });
        foreach ([0, 99999, 100000, 150000, 200000, 200001] as $salary) {
            DB::table('diplomes')->insert(['libelle' => 'LICENCE', 'salaire_brut' => $salary]);
        }
        foreach ([DiplomeController::class, \App\Http\Controllers\Api\Parametrage\DiplomeController::class] as $class) {
            foreach ([
                [['salaire_min' => 100000, 'salaire_max' => 200000], [100000, 150000, 200000]],
                [['salaire_min' => 150000, 'salaire_max' => 150000], [150000]],
                [['salaire_min' => 0, 'salaire_max' => 0], [0]],
                [['salaire_min' => 300000, 'salaire_max' => 400000], []],
            ] as [$filters, $expected]) {
                $request = Request::create('/diplomes', 'GET', $filters);
                $this->app->instance('request', $request);
                $payload = (new $class)->index($request)->getData(true);
                $this->assertEquals($expected, array_column($payload['data'], 'salaire_brut'));
            }
            try {
                (new $class)->index(Request::create('/diplomes', 'GET', ['salaire_min' => 200000, 'salaire_max' => 100000]));
                $this->fail('An inverted range must be rejected.');
            } catch (ValidationException $exception) {
                $this->assertArrayHasKey('salaire_max', $exception->errors());
            }
        }
    }
}
