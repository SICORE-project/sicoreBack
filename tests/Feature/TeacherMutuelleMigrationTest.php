<?php

namespace Tests\Feature;

use App\Models\Personnel\Enseignant;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class TeacherMutuelleMigrationTest extends TestCase
{
    public function test_teacher_mutuelles_can_be_loaded_and_existing_membership_survives_rerun(): void
    {
        Schema::create('enseignants', function (Blueprint $table) {
            $table->id();
            $table->softDeletes();
        });
        $migration = require database_path('migrations/2026_09_10_180000_restore_teacher_mutuelle_tables.php');
        $migration->up();
        $teacherId = DB::table('enseignants')->insertGetId([]);
        $teacher = Enseignant::findOrFail($teacherId);
        $this->assertCount(0, $teacher->load('mutuelles')->mutuelles);
        $mutuelleId = DB::table('mutuelles')->insertGetId(['nom' => 'Mutuelle test']);
        $teacher->mutuelles()->attach($mutuelleId, ['numero_affiliation' => 'TEST-01', 'est_actif' => true]);
        $migration->up();
        $this->assertCount(1, $teacher->load('mutuelles')->mutuelles);
        $this->assertSame('TEST-01', $teacher->mutuelles->first()->pivot->numero_affiliation);
    }
}
