<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    
public function up(): void
{
   

    // Conserver les périodes déjà enregistrées.
    DB::table('delegations_credits')
        ->join(
            'periode_de_paies',
            'delegations_credits.periode_de_paie_id',
            '=',
            'periode_de_paies.id'
        )
        ->update([
            'delegations_credits.periode_paie' =>
                DB::raw('periode_de_paies.libelle'),
        ]);

    // Ne jamais supprimer l'ancienne colonne si une
    // période n'a pas été correctement transférée.
    if (
        DB::table('delegations_credits')
            ->whereNull('periode_paie')
            ->exists()
    ) {
        throw new \RuntimeException(
            'Certaines périodes de paie n’ont pas été transférées.'
        );
    }

    Schema::table('delegations_credits', function (Blueprint $table) {
        $table->dropForeign(['periode_de_paie_id']);
        $table->dropColumn('periode_de_paie_id');
    });
}

public function down(): void
{
    throw new \RuntimeException(
        'Rollback automatique indisponible : la période de paie est désormais une saisie libre.'
    );
}

};
