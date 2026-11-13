<?php

namespace Tests\Feature;

use App\Http\Controllers\Api\Parametrage\RubriquePaieController;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class RubriquePaieStatisticsTest extends TestCase
{
    public function test_statistics_count_items_by_teacher_status_and_monthly_items(): void
    {
        Schema::create('statuts_enseignant', function (Blueprint $table): void {
            $table->id();
            $table->string('code');
            $table->string('libelle');
            $table->string('categorie')->nullable();
        });
        Schema::create('rubrique_paies', function (Blueprint $table): void {
            $table->id();
            $table->string('code');
            $table->string('libelle');
            $table->string('type');
            $table->string('periodicite');
            $table->decimal('montant', 15, 2)->nullable();
            $table->foreignId('statut_enseignant_id')->nullable();
            $table->timestamps();
        });
        DB::table('statuts_enseignant')->insert([
            ['id' => 1, 'code' => 'PC', 'libelle' => 'Professeurs contractuels', 'categorie' => 'contractuel'],
            ['id' => 2, 'code' => 'VAC', 'libelle' => 'Vacataires', 'categorie' => null],
            ['id' => 3, 'code' => 'FONC', 'libelle' => 'Fonctionnaires', 'categorie' => 'fonctionnaire'],
        ]);
        foreach ([
            ['R1', 'gain', 'mensuelle', 1], ['R2', 'retenue', 'mensuelle', 1], ['R3', 'gain', 'ponctuelle', 2],
            ['R4', 'gain', 'annuelle', 3], ['R5', 'retenue', 'mensuelle', null],
        ] as [$code, $type, $periodicite, $statut]) {
            DB::table('rubrique_paies')->insert(['code' => $code, 'libelle' => $code, 'type' => $type,
                'periodicite' => $periodicite, 'statut_enseignant_id' => $statut]);
        }

        $statistics = app(RubriquePaieController::class)->index(Request::create('/api/rubriques-paie'))->getData(true)['statistics'];

        $this->assertSame(['total' => 5, 'gains' => 3, 'retenues' => 2, 'contractuels' => 2, 'vacataires' => 1, 'mensuelles' => 3], $statistics);
    }
}
