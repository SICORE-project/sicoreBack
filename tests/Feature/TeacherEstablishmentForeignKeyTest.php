<?php

namespace Tests\Feature;

use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class TeacherEstablishmentForeignKeyTest extends TestCase
{
    public function test_migration_prevents_cascade_deletion_and_preserves_existing_teachers(): void
    {
        Schema::create('lieu_de_services', function (Blueprint $table) { $table->id(); });
        Schema::create('enseignants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lieu_service_id')->nullable()->constrained('lieu_de_services')->cascadeOnDelete();
        });
        DB::table('lieu_de_services')->insert([['id' => 1], ['id' => 2]]);
        DB::table('enseignants')->insert(['id' => 1, 'lieu_service_id' => 1]);
        $migration = require database_path('migrations/2026_09_11_130000_protect_teacher_establishment_foreign_key.php');
        $migration->up();
        $migration->up();
        $migration->down();
        try {
            DB::table('lieu_de_services')->where('id', 1)->delete();
            $this->fail('La suppression d’un établissement référencé doit être refusée.');
        } catch (QueryException) {
            $this->assertDatabaseHas('enseignants', ['id' => 1, 'lieu_service_id' => 1]);
            $this->assertDatabaseHas('lieu_de_services', ['id' => 1]);
        }
        DB::table('lieu_de_services')->where('id', 2)->delete();
        $this->assertDatabaseMissing('lieu_de_services', ['id' => 2]);
    }
}
