<?php

namespace Tests\Feature;

use App\Models\Parametrage\Syndicat;
use Tests\TestCase;

class SyndicatRecreationTest extends TestCase
{
    public function test_deleted_syndicat_can_be_recreated_and_becomes_visible(): void
    {
        $this->withoutMiddleware();
        $migration = require database_path('migrations/2026_07_27_161530_syndicats_table.php');
        $migration->up();
        $syndicat = Syndicat::create([
            'code' => 'S01', 'libelle' => 'Syndicat test', 'montant_check_off' => 100,
        ]);
        $syndicat->delete();

        $this->postJson('/api/syndicats', [
            'code' => ' s01 ', 'libelle' => 'Syndicat test',
            'montant_check_off' => 200, 'montant_oeuvre_sociale' => 50,
        ])->assertCreated()->assertJsonPath('data.id', $syndicat->id);

        $this->assertDatabaseHas('syndicats', [
            'id' => $syndicat->id, 'deleted_at' => null, 'montant_check_off' => 200,
        ]);
        $this->assertDatabaseCount('syndicats', 1);
        $this->getJson('/api/syndicats')->assertOk()->assertJsonFragment(['code' => 'S01']);

        $this->postJson('/api/syndicats', [
            'code' => 'S01', 'libelle' => 'Syndicat test',
        ])->assertUnprocessable()->assertJsonValidationErrors(['code', 'libelle']);
    }
}
