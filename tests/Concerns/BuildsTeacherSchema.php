<?php

namespace Tests\Concerns;

use App\Models\Personnel\Enseignant;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

trait BuildsTeacherSchema
{
    private function buildTeacherSchema(): void
    {
        (require database_path('migrations/2026_07_07_120005_create_enseignants_table.php'))->up();
        Schema::table('enseignants', fn (Blueprint $table) => $table->boolean('est_en_couple')->default(false));
        (require database_path('migrations/2026_09_22_000001_add_administrative_status_and_indice_to_enseignants.php'))->up();
        (require database_path('migrations/2026_09_22_000002_store_teacher_indice_as_text.php'))->up();
        (require database_path('migrations/2026_09_22_000003_make_teacher_indice_unique.php'))->up();
        $teacher = new Enseignant;
        foreach (['ia', 'ief', 'corps', 'categorie', 'discipline', 'diplome', 'lieuService', 'comptesBancaires', 'syndicats', 'mutuelles'] as $name) {
            $relation = $teacher->$name();
            $table = $relation->getRelated()->getTable();
            if (! Schema::hasTable($table)) {
                Schema::create($table, function (Blueprint $table): void {
                    $table->id();
                    $table->string('libelle')->nullable();
                    $table->string('code')->nullable();
                    foreach (['enseignant_id', 'ia_id', 'ief_id', 'corps_id', 'categorie_id'] as $column) {
                        $table->integer($column)->nullable();
                    }
                    $table->decimal('salaire_brut', 15, 2)->nullable();
                    $table->softDeletes();
                });
            }
            if ($relation instanceof \Illuminate\Database\Eloquent\Relations\BelongsToMany) {
                Schema::create($relation->getTable(), function (Blueprint $table) use ($relation): void {
                    $table->integer($relation->getForeignPivotKeyName());
                    $table->integer($relation->getRelatedPivotKeyName());
                    foreach ($relation->getPivotColumns() as $column) {
                        $table->string($column)->nullable();
                    }
                });
            }
        }
        DB::table('ias')->insert(['id' => 1, 'libelle' => 'Dakar']);
        DB::table('iefs')->insert(['id' => 2, 'ia_id' => 1, 'libelle' => 'Dakar Plateau']);
        DB::table('corps_enseignant')->insert([
            ['id' => 3, 'libelle' => 'Vacataire', 'code' => 'vac'],
            ['id' => 4, 'libelle' => 'Contractuel', 'code' => 'CTR'],
            ['id' => 5, 'libelle' => 'Fonctionnaire', 'code' => 'FONC'],
        ]);
        DB::table('categories')->insert(['id' => 5, 'corps_id' => 4, 'libelle' => 'A1']);
        DB::table('diplomes')->insert(['id' => 6, 'categorie_id' => 5, 'libelle' => 'Licence', 'salaire_brut' => 250000]);
        Schema::create('users', function (Blueprint $table): void {
            $table->id();
        });
        DB::table('users')->insert(['id' => 8]);
        Schema::create('payroll_audit_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users');
            $table->string('action');
            $table->string('auditable_type');
            $table->unsignedBigInteger('auditable_id')->nullable();
            $table->json('before')->nullable();
            $table->json('after')->nullable();
            $table->string('ip_address')->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }
}
