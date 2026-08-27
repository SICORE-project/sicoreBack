<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('permission_groupes', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('libelle', 100);
            $table->boolean('est_actif')->default(true);
            $table->timestamps();
        });

        Schema::create('permission_modules', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('libelle', 100);
            $table->foreignId('groupe_id')->nullable()->constrained('permission_groupes')->nullOnDelete();
            $table->boolean('est_actif')->default(true);
            $table->timestamps();
        });

        if (! Schema::hasTable('permissions')) {
            return;
        }

        $now = now();
        $groupes = DB::table('permissions')->whereNotNull('groupe')->where('groupe', '<>', '')
            ->select('groupe')->distinct()->pluck('groupe');

        foreach ($groupes as $groupe) {
            DB::table('permission_groupes')->insertOrIgnore([
                'code' => $groupe,
                'libelle' => ucfirst(str_replace(['_', '-'], ' ', $groupe)),
                'est_actif' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $modules = DB::table('permissions')->whereNotNull('module')->where('module', '<>', '')
            ->whereNotNull('groupe')->where('groupe', '<>', '')
            ->select('module', 'groupe')->distinct()->get();

        foreach ($modules as $module) {
            $groupeId = DB::table('permission_groupes')->where('code', $module->groupe)->value('id');
            DB::table('permission_modules')->insertOrIgnore([
                'code' => $module->module,
                'libelle' => ucfirst(str_replace(['_', '-'], ' ', $module->module)),
                'groupe_id' => $groupeId,
                'est_actif' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('permission_modules');
        Schema::dropIfExists('permission_groupes');
    }
};
