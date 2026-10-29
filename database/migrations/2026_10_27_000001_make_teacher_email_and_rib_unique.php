<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('enseignants')->where('email', '')->update(['email' => null]);

        foreach (DB::table('comptes_bancaires_enseignants')
            ->whereNull('rib')
            ->whereNotNull('code_banque')
            ->whereNotNull('code_guichet')
            ->whereNotNull('numero_compte')
            ->whereNotNull('cle_rib')
            ->get(['id', 'code_banque', 'code_guichet', 'numero_compte', 'cle_rib']) as $account) {
            DB::table('comptes_bancaires_enseignants')
                ->where('id', $account->id)
                ->update(['rib' => $account->code_banque.$account->code_guichet.$account->numero_compte.$account->cle_rib]);
        }

        if (DB::table('enseignants')
            ->select('email')
            ->whereNotNull('email')
            ->groupBy('email')
            ->havingRaw('COUNT(*) > 1')
            ->exists()) {
            throw new RuntimeException('Des enseignants partagent déjà une adresse e-mail. Corrigez ces doublons avant d’appliquer la contrainte d’unicité.');
        }

        if (DB::table('comptes_bancaires_enseignants')
            ->select('rib')
            ->whereNotNull('rib')
            ->groupBy('rib')
            ->havingRaw('COUNT(*) > 1')
            ->exists()) {
            throw new RuntimeException('Des enseignants partagent déjà un RIB complet. Corrigez ces doublons avant d’appliquer la contrainte d’unicité.');
        }

        Schema::table('enseignants', function (Blueprint $table): void {
            $table->unique('email');
        });

        Schema::table('comptes_bancaires_enseignants', function (Blueprint $table): void {
            $table->unique('rib');
        });
    }

    public function down(): void
    {
        Schema::table('comptes_bancaires_enseignants', function (Blueprint $table): void {
            $table->dropUnique(['rib']);
        });

        Schema::table('enseignants', function (Blueprint $table): void {
            $table->dropUnique(['email']);
        });
    }
};
