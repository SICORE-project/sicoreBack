<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('rubrique_paies', 'est_actif')) {
            Schema::table('rubrique_paies', function (Blueprint $table): void {
                $table->dropColumn('est_actif');
            });
        }
    }

    public function down(): void
    {
        // Le statut supprimé n'est volontairement pas restauré.
    }
};
