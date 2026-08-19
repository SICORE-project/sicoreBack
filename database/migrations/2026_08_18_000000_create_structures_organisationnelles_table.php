<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Mise à niveau des installations où lieu_de_services existe déjà.
        Schema::table('ias', function (Blueprint $table) {
            if (! Schema::hasColumn('ias', 'est_actif')) {
                $table->boolean('est_actif')->default(true)->index();
            }
            if (! Schema::hasColumn('ias', 'deleted_at')) {
                $table->softDeletes();
            }
        });

        Schema::table('lieu_de_services', function (Blueprint $table) {
            if (! Schema::hasColumn('lieu_de_services', 'perimetre')) {
                $table->enum('perimetre', ['national', 'regional'])->nullable()->index();
            }
            if (! Schema::hasColumn('lieu_de_services', 'type')) {
                $table->enum('type', ['DRH', 'DAGE', 'DECPC', 'IA', 'IEF'])->nullable()->index();
            }
            if (! Schema::hasColumn('lieu_de_services', 'est_actif')) {
                $table->boolean('est_actif')->default(true)->index();
            }
            if (! Schema::hasColumn('lieu_de_services', 'deleted_at')) {
                $table->softDeletes();
            }
        });

        DB::table('lieu_de_services')->whereIn('code', ['DRH', 'DAGE', 'DECPC'])->update([
            'perimetre' => 'national',
        ]);
        DB::table('lieu_de_services')->whereNotIn('code', ['DRH', 'DAGE', 'DECPC'])->whereNull('perimetre')->update([
            'perimetre' => 'regional',
        ]);
        foreach (['DRH', 'DAGE', 'DECPC'] as $type) {
            DB::table('lieu_de_services')->where('code', $type)->update(['type' => $type]);
        }
        DB::table('lieu_de_services')->whereNotNull('ief_id')->whereNull('type')->update(['type' => 'IEF']);
        DB::table('lieu_de_services')->whereNotNull('ia_id')->whereNull('ief_id')->whereNull('type')->update(['type' => 'IA']);
    }

    public function down(): void
    {
        // Les colonnes font désormais partie du modèle métier lieu_de_services.
    }
};