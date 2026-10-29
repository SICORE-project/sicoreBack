<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::table('enseignants')
            ->select('cni')
            ->whereNotNull('cni')
            ->groupBy('cni')
            ->havingRaw('COUNT(*) > 1')
            ->exists()) {
            throw new RuntimeException('Des enseignants partagent déjà un numéro de carte d’identité. Corrigez ces doublons avant d’appliquer la contrainte d’unicité.');
        }

        Schema::table('enseignants', function (Blueprint $table): void {
            // NULL remains allowed for legacy records without a CNI.
            $table->unique('cni');
        });
    }

    public function down(): void
    {
        Schema::table('enseignants', function (Blueprint $table): void {
            $table->dropUnique(['cni']);
        });
    }
};
