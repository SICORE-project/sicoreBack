<?php

namespace Tests\Feature;

use App\Models\Enseignant;
use App\Models\Personnel\Enseignant as PersonnelEnseignant;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class TeacherIaFromIefTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Schema::create('iefs', function (Blueprint $t) { $t->id(); $t->integer('ia_id'); $t->string('libelle'); $t->softDeletes(); });
        Schema::create('enseignants', function (Blueprint $t) {
            $t->id(); $t->string('nom')->nullable(); $t->integer('ia_id')->nullable(); $t->integer('ief_id')->nullable();
            $t->integer('statut_enseignant_id')->nullable(); $t->string('type_engagement')->nullable(); $t->timestamps(); $t->softDeletes();
        });
        DB::table('iefs')->insert([['id' => 10, 'ia_id' => 1, 'libelle' => 'IEF Dakar'], ['id' => 20, 'ia_id' => 2, 'libelle' => 'IEF Thiès']]);
    }

    public function test_ia_is_derived_from_ief_on_create(): void
    {
        $teacher = Enseignant::create(['nom' => 'A', 'ief_id' => 20]);
        $this->assertSame(2, (int) $teacher->fresh()->ia_id);
    }

    public function test_changing_ief_moves_ia_and_contradictory_ia_is_corrected(): void
    {
        $teacher = Enseignant::create(['nom' => 'A', 'ia_id' => 1, 'ief_id' => 10]);
        $teacher->update(['ief_id' => 20]);
        $this->assertSame(2, (int) $teacher->fresh()->ia_id);
        $teacher->update(['ia_id' => 1]);
        $this->assertSame(2, (int) $teacher->fresh()->ia_id);
    }

    public function test_personnel_model_applies_the_same_rule(): void
    {
        $teacher = PersonnelEnseignant::query()->create(['nom' => 'B', 'ia_id' => 1, 'ief_id' => 20]);
        $this->assertSame(2, (int) DB::table('enseignants')->where('id', $teacher->id)->value('ia_id'));
    }
}
