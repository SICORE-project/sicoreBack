<?php

namespace Tests\Feature;

use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class GuichetCodeUniquenessTest extends TestCase
{
    public function test_existing_global_index_is_replaced_by_uniqueness_per_bank(): void
    {
        Schema::create('guichets', function (Blueprint $table) {
            $table->string('code', 5)->unique();
            $table->unsignedBigInteger('institut_financier_id');
            $table->string('libelle');
            $table->unique(['institut_financier_id', 'code']);
        });

        DB::table('guichets')->insert([
            'code' => '10000', 'institut_financier_id' => 1, 'libelle' => 'Premier guichet',
        ]);

        $migration = require database_path('migrations/2026_11_03_000000_scope_guichet_code_uniqueness_to_bank.php');
        $migration->up();

        DB::table('guichets')->insert([
            'code' => '10000', 'institut_financier_id' => 2, 'libelle' => 'Autre banque',
        ]);

        $this->assertDatabaseCount('guichets', 2);
        $this->assertDatabaseHas('guichets', [
            'code' => '10000', 'institut_financier_id' => 1, 'libelle' => 'Premier guichet',
        ]);

        $this->expectException(QueryException::class);
        DB::table('guichets')->insert([
            'code' => '10000', 'institut_financier_id' => 1, 'libelle' => 'Doublon interdit',
        ]);
    }
}
