<?php
namespace Tests\Feature;

use App\Models\Personnel\Enseignant;
use App\Services\Administration\Personnel\EnseignantService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class TeacherSearchTest extends TestCase
{
    public function test_filters_are_combined_before_pagination(): void
    {
        Schema::create('enseignants', function ($t) {
            $t->id(); $t->string('prenom'); $t->string('nom'); $t->softDeletes();
            foreach (['corps_id','diplome_id','ia_id','ief_id'] as $field) $t->integer($field);
        });
        $teacher = new Enseignant;
        foreach (['ia','ief','corps','categorie','discipline','diplome','lieuService','comptesBancaires','syndicats','mutuelles'] as $name) {
            $relation = $teacher->$name();
            $table = $relation->getRelated()->getTable();
            if (!Schema::hasTable($table)) Schema::create($table, function ($t) { $t->id(); $t->integer('enseignant_id')->nullable(); $t->softDeletes(); });
            if ($relation instanceof \Illuminate\Database\Eloquent\Relations\BelongsToMany) {
                Schema::create($relation->getTable(), function ($t) use ($relation) {
                    $t->integer($relation->getForeignPivotKeyName());
                    $t->integer($relation->getRelatedPivotKeyName());
                    foreach ($relation->getPivotColumns() as $column) $t->string($column)->nullable();
                });
            }
        }
        DB::table('enseignants')->insert([
            ['id'=>1,'prenom'=>'Awa','nom'=>'Diop','corps_id'=>1,'diplome_id'=>2,'ia_id'=>3,'ief_id'=>4],
            ['id'=>2,'prenom'=>'Awa','nom'=>'Diop','corps_id'=>1,'diplome_id'=>2,'ia_id'=>3,'ief_id'=>5],
            ['id'=>3,'prenom'=>'Moussa','nom'=>'Fall','corps_id'=>2,'diplome_id'=>3,'ia_id'=>4,'ief_id'=>6],
        ]);
        $service = new EnseignantService;
        $result = $service->paginate(1, ['prenom'=>'aw','nom'=>'DIO','corps_id'=>1,'diplome_id'=>2,'ia_id'=>3,'ief_id'=>4]);
        $this->assertSame(1, $result->total());
        $this->assertSame(1, $result->first()->id);
        $this->assertSame(2, $service->paginate(1, ['prenom'=>'awa'])->total());
        $this->assertSame(0, $service->paginate(20, ['nom'=>'Inconnu'])->total());
        $this->assertSame(2, $service->paginate(1, ['search'=>'awa DIO'])->total());
        $this->assertSame(2, $service->paginate(1, ['search'=>'diop awa'])->total());
        $this->assertSame(1, $service->paginate(20, ['search'=>'Awa Diop', 'ief_id'=>4])->total());
        $this->assertSame(0, $service->paginate(20, ['search'=>'Awa Fall'])->total());
    }
}
