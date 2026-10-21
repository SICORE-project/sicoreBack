<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ForeignKeyDeleteErrorTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('test_parents', function (Blueprint $table): void {
            $table->id();
        });
        Schema::create('test_enfants', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('parent_id')->constrained('test_parents')->restrictOnDelete();
        });

        Route::delete('/api/test-parent/{id}', function (int $id) {
            DB::table('test_parents')->where('id', $id)->delete();

            return response()->json(['message' => 'Supprimé.']);
        });
    }

    public function test_une_suppression_associee_retourne_un_message_clair(): void
    {
        $parentId = DB::table('test_parents')->insertGetId([]);
        DB::table('test_enfants')->insert(['parent_id' => $parentId]);

        $this->deleteJson("/api/test-parent/{$parentId}")
            ->assertStatus(409)
            ->assertJsonPath(
                'message',
                'Suppression impossible : cet élément est associé à d’autres données. Supprimez ou dissociez d’abord les éléments liés.'
            );

        $this->assertDatabaseHas('test_parents', ['id' => $parentId]);
    }
}
